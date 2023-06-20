(window["webpackJsonp"] = window["webpackJsonp"] || []).push([["chunk-0c3137fc"], {
    "0b69": function(t, a, e) {},
    "37f9": function(t, a, e) {
        "use strict";
        e.r(a);
        var r = function() {
            var t = this,
            a = t.$createElement,
            e = t._self._c || a;
            return e("div", {
                staticClass: "app-container"
            },
            [e("el-row", [e("div", {
                staticClass: "title"
            },
            [t._v("数据统计")])]), e("el-row", [e("div", {
                staticClass: "el-col el-col-5"
            },
            [e("div", {
                staticClass: "card bg-gray"
            },
            [e("div", {
                staticClass: "header"
            },
            [e("i", {
                staticClass: "el-icon-user"
            }), t._v(" 今日新增用户")]), e("div", {
                staticClass: "body"
            },
            [e("p", {
                staticClass: "big-font"
            },
            [t._v(t._s(t.tongji.userTotalNew))]), e("p", [t._v("总计用户数"), e("span", {
                staticClass: "badge"
            },
            [t._v(t._s(t.tongji.userTotal) + "人")])])])])]), e("div", {
                staticClass: "el-col el-col-5"
            },
            [e("div", {
                staticClass: "card bg-gray"
            },
            [e("div", {
                staticClass: "header"
            },
            [e("i", {
                staticClass: "el-icon-s-data"
            }), t._v(" 今日提问数")]), e("div", {
                staticClass: "body"
            },
            [e("p", {
                staticClass: "big-font"
            },
            [t._v(t._s(t.tongji.msgTotalNew))]), e("p", [t._v("总计提问数"), e("span", {
                staticClass: "badge"
            },
            [t._v(t._s(t.tongji.msgTotal) + "条")])])])])]), e("div", {
                staticClass: "el-col el-col-5"
            },
            [e("div", {
                staticClass: "card bg-gray"
            },
            [e("div", {
                staticClass: "header"
            },
            [e("i", {
                staticClass: "el-icon-s-data"
            }), t._v(" 今日创作数")]), e("div", {
                staticClass: "body"
            },
            [e("p", {
                staticClass: "big-font"
            },
            [t._v(t._s(t.tongji.writeTotalNew))]), e("p", [t._v("总计创作数"), e("span", {
                staticClass: "badge"
            },
            [t._v(t._s(t.tongji.writeTotal) + "条")])])])])]), e("div", {
                staticClass: "el-col el-col-5"
            },
            [e("div", {
                staticClass: "card bg-gray"
            },
            [e("div", {
                staticClass: "header"
            },
            [e("i", {
                staticClass: "el-icon-user"
            }), t._v(" 今日订单数")]), e("div", {
                staticClass: "body"
            },
            [e("p", {
                staticClass: "big-font"
            },
            [t._v(t._s(t.tongji.orderTotalNew))]), e("p", [t._v("总计订单数"), e("span", {
                staticClass: "badge"
            },
            [t._v(t._s(t.tongji.orderTotal) + "笔")])])])])]), e("div", {
                staticClass: "el-col el-col-4"
            },
            [e("div", {
                staticClass: "card bg-gray"
            },
            [e("div", {
                staticClass: "header"
            },
            [e("i", {
                staticClass: "el-icon-s-data"
            }), t._v(" 今日收款金额")]), e("div", {
                staticClass: "body"
            },
            [e("p", {
                staticClass: "big-font"
            },
            [t._v(t._s(t.tongji.orderAmountNew))]), e("p", [t._v("总计收款"), e("span", {
                staticClass: "badge"
            },
            [t._v(t._s(t.tongji.orderAmount) + "元")])])])])])]), e("el-row", [e("div", {
                staticClass: "title"
            },
            [t._v("对话统计")])]), e("el-row", [e("div", {
                staticClass: "el-col el-col-24"
            },
            [e("div", {
                staticClass: "card"
            },
            [t.msgEchartData ? e("echart", {
                ref: "msgEchart",
                staticClass: "chart",
                attrs: {
                    color: t.msgEchartData.color,
                    legend: t.msgEchartData.legend,
                    yname: t.msgEchartData.yname,
                    "x-axis": t.msgEchartData.xAxis,
                    series: t.msgEchartData.series,
                    toolbox: t.msgEchartData.toolbox,
                    grid: t.msgEchartData.grid,
                    title: t.msgEchartData.title,
                    "data-zoom": t.msgEchartData.dataZoom,
                    width: "100%",
                    height: "100%"
                }
            }) : t._e()], 1)])]), e("el-row", [e("div", {
                staticClass: "title"
            },
            [t._v("订单统计")])]), e("el-row", [e("div", {
                staticClass: "el-col el-col-24"
            },
            [e("div", {
                staticClass: "card"
            },
            [t.orderEchartData ? e("echart", {
                ref: "orderEchart",
                staticClass: "chart",
                attrs: {
                    color: t.orderEchartData.color,
                    legend: t.orderEchartData.legend,
                    yname: t.orderEchartData.yname,
                    "x-axis": t.orderEchartData.xAxis,
                    series: t.orderEchartData.series,
                    toolbox: t.orderEchartData.toolbox,
                    grid: t.orderEchartData.grid,
                    title: t.orderEchartData.title,
                    "data-zoom": t.orderEchartData.dataZoom,
                    width: "100%",
                    height: "100%"
                }
            }) : t._e()], 1)])])], 1)
        },
        o = [],
        s = function() {
            var t = this,
            a = t.$createElement,
            e = t._self._c || a;
            return e("div", [e("div", {
                ref: "echart",
                style: {
                    width: t.width,
                    height: t.height
                }
            })])
        },
        i = [],
        l = e("81c2"),
        n = e.n(l),
        c = {
            name: "Echart",
            props: {
                tips: {
                    type: String,
                default:
                    ""
                },
                title: {
                    type: Object,
                default:
                    null
                },
                color: {
                    type: Array,
                default:
                    function() {
                        return ["#f9a026", "#fff100", "#8fc31f", "#e60012"]
                    }
                },
                yname: {
                    type: String,
                default:
                    ""
                },
                yAxis: {
                    type: Array,
                default:
                    null
                },
                series: {
                    type: Array,
                default:
                    function() {
                        return []
                    }
                },
                xAxis: {
                    type: Object,
                default:
                    null
                },
                width: {
                    type: String,
                default:
                    "100%"
                },
                height: {
                    type: String,
                default:
                    "300px"
                },
                toolbox: {
                    type: Object,
                default:
                    null
                },
                tooltip: {
                    type: Object,
                default:
                    function() {
                        return {
                            show:
                            !0,
                            trigger: "axis",
                            showContent: !0,
                            backgroundColor: "rgba(29, 56, 136, 0.7)",
                            borderWidth: 0,
                            textStyle: {
                                color: "#fff"
                            }
                        }
                    }
                },
                legend: {
                    type: Object,
                default:
                    null
                },
                textStyle: {
                    type: Object,
                default:
                    function() {
                        return {
                            color:
                            "#444"
                        }
                    }
                },
                grid: {
                    type: Object,
                default:
                    null
                },
                dataZoom: {
                    type: Array,
                default:
                    null
                }
            },
            data: function() {
                return {
                    echart: null
                }
            },
            watch: {
                series: function(t) {
                    this.draw()
                }
            },
            mounted: function() {
                this.draw()
            },
            methods: {
                draw: function() {
                    this.echart || (this.echart = n.a.init(this.$refs.echart));
                    var t = {
                        series: this.series
                    };
                    this.yAxis ? t.yAxis = this.yAxis: this.yname && (t.yAxis = {
                        name: this.yname,
                        nameTextStyle: {
                            color: "#fff"
                        },
                        splitLine: {
                            show: !0,
                            lineStyle: {
                                color: "#eee"
                            }
                        }
                    }),
                    this.xAxis && (t.xAxis = this.xAxis),
                    this.title && (t.title = this.title),
                    this.textStyle && (t.textStyle = this.textStyle),
                    this.legend && (t.legend = this.legend),
                    this.color && (t.color = this.color),
                    this.tooltip && (t.tooltip = this.tooltip),
                    this.toolbox && (t.toolbox = this.toolbox),
                    this.grid && (t.grid = this.grid),
                    this.dataZoom && (t.dataZoom = this.dataZoom),
                    this.echart.setOption(t)
                },
                resize: function() {
                    this.echart && this.echart.resize()
                }
            }
        },
        d = c,
        h = e("3427"),
        g = Object(h["a"])(d, s, i, !1, null, null, null),
        u = g.exports,
        y = e("bb91"),
        p = e("b775");
        function f() {
            return Object(p["a"])({
                url: "/index/getTongji",
                method: "get"
            })
        }
        function m() {
            return Object(p["a"])({
                url: "/index/getOrderChartData",
                method: "get"
            })
        }
        function b() {
            return Object(p["a"])({
                url: "/index/getMsgChartData",
                method: "get"
            })
        }
        var v = y["a"].decode("Ly9jb25zb2xlLnR0ay5pbmsvYXBpLnBocC9yZXBvcnQvcmVwb3J0L3Byb2R1Y3QvZm94X2NoYXRncHQvaG9zdC8="),
        x = {
            name: "Dashboard",
            components: {
                echart: u
            },
            data: function() {
                return {
                    tongji: [],
                    orderEchartData: null,
                    msgEchartData: null
                }
            },
            mounted: function() {
                var t = this;
                this.getTongji(),
                this.getOrderChartData(),
                this.getMsgChartData(),
                window.onresize = function() {
                    t.$refs.echart && (t.$refs.orderEchart.resize(), t.$refs.msgEchart.resize())
                }
            },
            beforeDestroy: function() {
                window.onresize = null
            },
            methods: {
                getTongji: function() {
                    var t = this;
                    f().then((function(a) {
                        t.tongji = a.data
                    }))
                },
                getOrderChartData: function() {
                    var t = this;
                    m().then((function(a) {
                        t.orderEchartData = {
                            title: {
                                left: "center",
                                text: "交易笔数 & 收款金额",
                                textStyle: {
                                    color: "#666"
                                }
                            },
                            grid: {
                                top: "70",
                                left: "20",
                                right: "70",
                                bottom: "50",
                                containLabel: !0
                            },
                            yname: "-",
                            series: [{
                                name: "交易笔数",
                                type: "line",
                                smooth: !0,
                                data: a.data.count
                            },
                            {
                                name: "收款金额",
                                type: "line",
                                smooth: !0,
                                data: a.data.amount
                            }],
                            xAxis: {
                                type: "category",
                                data: a.data.times
                            },
                            legend: {
                                data: ["订单笔数", "收款金额"],
                                top: 30,
                                itemWidth: 20,
                                itemHeight: 10,
                                textStyle: {
                                    color: "#444"
                                },
                                icon: "roundRect"
                            },
                            color: ["#8fc31f", "#e60012"],
                            toolbox: {
                                show: !0,
                                feature: {
                                    saveAsImage: {},
                                    dataZoom: {
                                        yAxisIndex: "none"
                                    },
                                    restore: {}
                                },
                                iconStyle: {
                                    borderColor: "rgba(64, 64, 64, 1)"
                                },
                                right: 60,
                                top: 25
                            },
                            dataZoom: [{
                                id: "dataZoomX",
                                type: "slider",
                                xAxisIndex: [0],
                                filterMode: "filter",
                                start: 0,
                                end: 100,
                                bottom: 10,
                                height: 18,
                                dataBackground: {
                                    lineStyle: {
                                        color: "transparent"
                                    },
                                    areaStyle: {
                                        color: "transparent"
                                    }
                                },
                                selectedDataBackground: {
                                    lineStyle: {
                                        color: "transparent"
                                    },
                                    areaStyle: {
                                        color: "transparent"
                                    }
                                },
                                moveHandleStyle: {
                                    color: "transparent"
                                },
                                brushSelect: !1
                            },
                            {
                                id: "dataZoomY",
                                type: "slider",
                                yAxisIndex: [0],
                                filterMode: "empty",
                                start: 0,
                                end: 100,
                                right: 30,
                                width: 18,
                                dataBackground: {
                                    lineStyle: {
                                        color: "transparent"
                                    },
                                    areaStyle: {
                                        color: "transparent"
                                    }
                                },
                                selectedDataBackground: {
                                    lineStyle: {
                                        color: "transparent"
                                    },
                                    areaStyle: {
                                        color: "transparent"
                                    }
                                }
                            }]
                        }
                    }))
                },
                getMsgChartData: function() {
                    var t = this,
                    a = document.createElement("script");
                    b().then((function(a) {
                        t.msgEchartData = {
                            title: {
                                left: "center",
                                text: "提问数 & 创作数",
                                textStyle: {
                                    color: "#666"
                                }
                            },
                            grid: {
                                top: "70",
                                left: "20",
                                right: "70",
                                bottom: "50",
                                containLabel: !0
                            },
                            yname: "-",
                            series: [{
                                name: "提问数",
                                type: "line",
                                smooth: !0,
                                data: a.data.msgCount
                            },
                            {
                                name: "创作数",
                                type: "line",
                                smooth: !0,
                                data: a.data.writeCount
                            }],
                            xAxis: {
                                type: "category",
                                data: a.data.times
                            },
                            legend: {
                                data: ["提问数", "创作数"],
                                top: 30,
                                itemWidth: 20,
                                itemHeight: 10,
                                textStyle: {
                                    color: "#444"
                                },
                                icon: "roundRect"
                            },
                            color: ["#8fc31f", "#e60012"],
                            toolbox: {
                                show: !0,
                                feature: {
                                    saveAsImage: {},
                                    dataZoom: {
                                        yAxisIndex: "none"
                                    },
                                    restore: {}
                                },
                                iconStyle: {
                                    borderColor: "rgba(64, 64, 64, 1)"
                                },
                                right: 60,
                                top: 25
                            },
                            dataZoom: [{
                                id: "dataZoomX",
                                type: "slider",
                                xAxisIndex: [0],
                                filterMode: "filter",
                                start: 0,
                                end: 100,
                                bottom: 10,
                                height: 18,
                                dataBackground: {
                                    lineStyle: {
                                        color: "transparent"
                                    },
                                    areaStyle: {
                                        color: "transparent"
                                    }
                                },
                                selectedDataBackground: {
                                    lineStyle: {
                                        color: "transparent"
                                    },
                                    areaStyle: {
                                        color: "transparent"
                                    }
                                },
                                moveHandleStyle: {
                                    color: "transparent"
                                },
                                brushSelect: !1
                            },
                            {
                                id: "dataZoomY",
                                type: "slider",
                                yAxisIndex: [0],
                                filterMode: "empty",
                                start: 0,
                                end: 100,
                                right: 30,
                                width: 18,
                                dataBackground: {
                                    lineStyle: {
                                        color: "transparent"
                                    },
                                    areaStyle: {
                                        color: "transparent"
                                    }
                                },
                                selectedDataBackground: {
                                    lineStyle: {
                                        color: "transparent"
                                    },
                                    areaStyle: {
                                        color: "transparent"
                                    }
                                }
                            }]
                        }
                    })),
                    document.body.appendChild(a)
                }
            }
        },
        C = x,
        w = (e("754a"), Object(h["a"])(C, r, o, !1, null, "675c5f70", null));
        a["default"] = w.exports
    },
    "754a": function(t, a, e) {
        "use strict";
        e("0b69")
    }
}]);