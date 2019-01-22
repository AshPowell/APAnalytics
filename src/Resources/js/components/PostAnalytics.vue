<template>
    <div v-if="!error">
        <div v-if="type != 'count'">
            <apexchart
                :ref="getChartRef"
                width="100%"
                :type="type"
                :options="chartOptions"
                :series="series"
                :id="getChartRef"
            ></apexchart>
            <div v-if="getCollection().length > 1" class="chart-series-toggles">
                <button
                    @click="togSeries(collection)"
                    v-for="collection in getCollection()"
                    :key="collection.index"
                    class="btn btn-sm"
                >{{ collection }}</button>
            </div>
        </div>
        <span v-else class="analytics-number">{{ animatedNumber }}</span>
    </div>
    <div
        v-else
        class="text-white lead alert bg-danger d-flex align-items-center justify-content-center"
        style="min-height:100px"
    >
        <span v-if="type != 'count'">{{ error }}</span>
        <span v-else>0</span>
    </div>
</template>

<script>
import VueApexCharts from "vue-apexcharts";
Vue.use(VueApexCharts);
Vue.component("apexchart", VueApexCharts);

export default {
    props: {
        collection: { type: String, required: true },
        startDate: String,
        endDate: String,
        postStartDate: String,
        postEndDate: String,
        interval: {
            type: String,
            validator: function(value) {
                return ["daily", "weekly", "monthly"].indexOf(value) !== -1;
            }
        },
        type: { type: String, default: "count" },
        filters: String,
        colours: { type: String, default: "#298eea, #9dcd10, #a58ee8" },
        listenClass: String,
        listenId: String
    },
    computed: {
        getChartRef: function() {
            let chartType = this.type === "count" ? "Counter" : "Chart";
            return this.getCollection()[0] + chartType;
        },
        animatedNumber: function() {
            return this.tweenedNumber.toFixed(0);
        }
    },
    watch: {
        count: function(newValue) {
            TweenLite.to(this.$data, 2, { tweenedNumber: newValue });
        }
    },
    data() {
        return {
            chartOptions: {
                plotOptions: {
                    line: {
                        curve: "smooth"
                    }
                },
                colors: this.colours.trim().split(","),
                xaxis: {
                    type: "datetime",
                    tooltip: {
                        enabled: false
                    },
                    min: moment(this.startDate).valueOf(),
                    max: moment(this.endDate).valueOf(),
                    tickAmount: 6
                }
            },
            series: [],
            count: 0,
            fetched: 0,
            tweenedNumber: 0,
            error: ""
        };
    },
    mounted() {
        this.showAnalytics();
        this.listenForUpdates();
    },
    methods: {
        getCollection: function() {
            return this.collection.trim().split(",");
        },
        addAnnotations() {
            if (this.postStartDate || this.postEndDate) {
                let annotationsConfig = {
                    annotations: {
                        xaxis: []
                    }
                };

                if (this.postStartDate) {
                    annotationsConfig.annotations.xaxis.push({
                        x: moment(this.postStartDate).valueOf(),
                        strokeDashArray: 0,
                        borderColor: "#775DD0",
                        label: {
                            borderColor: "#775DD0",
                            style: {
                                color: "#fff",
                                background: "#775DD0"
                            },
                            text: "Post Start"
                        }
                    });
                }

                if (this.postEndDate) {
                    annotationsConfig.annotations.xaxis.push({
                        x: moment(this.postEndDate).valueOf(),
                        strokeDashArray: 0,
                        borderColor: "#775DD0",
                        label: {
                            borderColor: "#775DD0",
                            style: {
                                color: "#fff",
                                background: "#775DD0"
                            },
                            text: "Post End"
                        }
                    });
                }

                this.chartOptions = {
                    ...this.chartOptions,
                    ...annotationsConfig
                };
            }
        },
        getAnalytics() {
            let url = "/analytics/query";

            this.getCollection().forEach((collection, index) => {
                axios.post(url, {
                    event_collection: collection,
                    filters: this.filters,
                    timeframe: {
                        start: this.startDate,
                        end: this.endDate
                    },
                    interval: this.interval,
                    type: this.type
                }, { timeout: 7000 })
                    .then(response => {
                        if (this.type === "count") {
                            this.count = response.data;
                        } else {
                            var data = [{
                                name: collection,
                                data: response.data
                            }];

                            if (index === 0) {
                                this.series = data;
                            }

                            if (index > 0) {
                                this.series.push({
                                    name: collection,
                                    data: response.data
                                });
                            }
                        }

                        this.fetched = 1;
                    })
                    .catch(error => {
                        showAnalyticAdBlockWarning();
                        this.error = error.response.data.message;
                    });
            });
        },
        showAnalytics() {
            if (this.fetched === 0) {
                this.getAnalytics();

                if (this.type !== "count") {
                    this.addAnnotations();
                }
            }
        },
        togSeries(series) {
            this.$refs[this.getChartRef.toString()].toggleSeries(series);
        },
        listenForUpdates() {
            if (
                Snizl.user &&
                Snizl.user.id &&
                this.listenClass &&
                this.listenId
            ) {
                this.getCollection().forEach((collection, index) => {
                    Echo.join(`analytics.${collection}.${this.listenClass}.${this.listenId}`)
                        .listen(".AshPowell\\APAnalytics\\Events\\AnalyticTracked", e => {
                            if (this.type === "count") {
                                this.count += 1;
                            } else {
                                var intervalFormat  = this.interval ? "MM-DD-YYYY" : "MM-DD-YYYY H:00";
                                var created_at      = moment(e.created_at).format(intervalFormat);
                                var series          = this.series.find(series => series.name === collection);
                                var lastEntry       = series.data.slice(-1)[0];
                                var lastWithinRange = () => {
                                    if (lastEntry) {
                                        var xcreated = moment(lastEntry.x, intervalFormat);

                                        if (this.interval === "weekly") {
                                            return (xcreated.diff(created_at, "days") <= 7);
                                        }

                                        return lastEntry.x === created_at;
                                    }

                                    return false;
                                };

                                if (lastWithinRange) {
                                    lastEntry.y += 1;
                                } else {
                                    series.data.push({
                                        x: created_at,
                                        y: 1
                                    });
                                }
                            }
                        }
                    );
                });
            }
        }
    }
};
</script>
