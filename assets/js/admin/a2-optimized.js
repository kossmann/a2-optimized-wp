function generateCircle(id, radius, width, graph_data) {
	if (!graph_data) { return; }
	let baseColor = palette[graph_data.color_class];

	var circle_graph = Circles.create({
		id: id,
		radius: radius,
		value: graph_data.score,
		maxValue: graph_data.max,
		width: width,
		text: graph_data.text,
		colors: [baseColor + '25', baseColor + '55'],
		duration: 400,
		wrpClass: 'circles-wrp',
		textClass: 'circles-text',
		valueStrokeClass: 'circles-valueStroke',
		maxValueStrokeClass: 'circles-maxValueStroke',
		styleWrapper: true,
		styleText: true
	});

	return circle_graph;
}

function createLineGraph(elemId, graphData, circleId) {
	if (!graphData) { return; }
	let lineDiv = document.getElementById(elemId);
	let bodyRect = document.body.getBoundingClientRect();
	let rect = lineDiv.getBoundingClientRect();
	var svg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
	svg.setAttribute("width", rect.width);
	svg.setAttribute("height", rect.height);

	var max_length = rect.width - 10;
	var progress_percent = graphData.score / graphData.max;
	if (progress_percent > 1) {
		progress_percent = 1;
	}
	var progress_length = max_length * progress_percent;

	var baseColor = palette[graphData.color_class];
	svg.appendChild(getLinePath(5, 5, max_length, baseColor + "25"));
	svg.appendChild(getLinePath(5, 5, progress_length, baseColor));

	lineDiv.innerHTML = '';
	lineDiv.appendChild(svg);


	if (circleId) {
		let circleDiv = document.getElementById(circleId);
		let centerPos = rect.width / 2;
		let maxOffset = centerPos - 35;
		let offset = progress_length - centerPos;
		if (offset < -1 * maxOffset) {
			offset = -1 * maxOffset;
		}
		else if (offset > maxOffset) {
			offset = maxOffset;
		}
		let graphDiv = circleDiv.querySelector('.circles-wrp');
		graphDiv.style.left = offset + "px";
	}
}

function getLinePath(x, y, length, color) {
	var path = document.createElementNS("http://www.w3.org/2000/svg", "path");
	path.setAttribute("fill", "transparent");
	path.setAttribute("stroke", color);
	path.setAttribute("stroke-width", "10");
	path.setAttribute("stroke-linecap", "round");
	path.setAttribute("stroke-linejoin", "round");
	path.setAttribute("class", "line-graph-style");
	path.setAttribute("d", "m " + x + "," + y + " h " + length + " Z");

	return path;

}

// thanks stackoverflow! https://stackoverflow.com/questions/1740700/how-to-get-hex-color-value-rather-than-rgb-value
function rgb2hex(rgb) {
	var match = rgb.match(/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/)
	return '#' + match.slice(1).map(n => parseInt(n, 10).toString(16).padStart(2, '0')).join('');
}

let palette = [];
document.addEventListener("DOMContentLoaded", function () {
	var palette_items = document.querySelectorAll("#color-palette span");
	palette_items.forEach(function (el, index, array) {
		var style = getComputedStyle(el);
		var element_color = style.color;
		var element_class = el.className;
		var hex = rgb2hex(element_color);
		palette[element_class] = hex;
	});
});

Vue.component('info-button', {
	props: { metric: { type: String } },
	template: `
	<div class="info-toggle-button">
		<span @click="toggleInfoDiv(metric, $event);"><span class="glyphicon glyphicon-question-sign" aria-hidden="true"></span></span>
	</div>
	`,
	methods: {
		toggleInfoDiv: function (metric, elem) {
			console.log('info button clicked for ' + metric);
			this.$root.toggleInfoDiv(metric);
		}
	}
});

Vue.component('graph-legend', {
	props: { metric: { type: String } },
	data() {
		return page_data.graphs[this.metric]
	},
	template: `
	<div class="col-sm-10 graph-legend ">
		<div class="row graph-legend-header">
			<div class="col-sm-4 success">
				good
			</div>
			<div class="col-sm-4 warn">
				not good
			</div>
			<div class="col-sm-4 danger">
				bad
			</div>
		</div>
		<div class="row">
			<div class="col-sm-4">
				&nbsp;
			</div>
			<div class="col-sm-4 left-label">
				<span>&nbsp;{{thresholds.warn}}</span>
			</div>
			<div class="col-sm-4 left-label">
				<span>&nbsp;{{thresholds.danger}}</span>
			</div>
		</div>
	</div>
	`
});

Vue.component('server-performance', {
	data() {
		return page_data
	},
	methods: {
		pageSpeedCheck: function () {
			this.$root.pageSpeedCheck('server-performance');
		},
		drawGraphs: function () {
			let perf = page_data.graphs;
			var circles_ttfb = generateCircle('circles-ttfb', 40, 10, perf.ttfb);
			var circles_cls = generateCircle('circles-cls', 40, 10, perf.cls);
			var circles_overall = generateCircle('circles-overall_score', 60, 20, perf.overall_score);

			//var offset_circles = jQuery('#circles-ttfb, #circles-cls, #circles-overall_score').find('.circles-text');
			//offset_circles.css({ 'left': '-32px', 'top': '-10px', 'font-size': '36px' });
			//var overall_circle = jQuery('#circles-overall_score').find('.circles-text');
			//overall_circle.css({ 'left': '-32px', 'top': '-10px', 'font-size': '60px' });

			var circles_lcp = generateCircle('circles-lcp', 35, 7, perf.lcp);
			var circles_fid = generateCircle('circles-fid', 35, 7, perf.fid);
			var circles_fcp = generateCircle('circles-fcp', 35, 7, perf.fcp);

			var line_graph_lcp = createLineGraph('line-graph-lcp', perf.lcp, 'circles-lcp');
			var line_graph_fid = createLineGraph('line-graph-fid', perf.fid, 'circles-fid');
			var line_graph_fcp = createLineGraph('line-graph-fcp', perf.fcp, 'circles-fcp');
		}
	},
	template: `
	<div class="col-sm-12">
		<div class="row" style="">
			<div class="col-sm-2 side-nav">
				<p><a href="#">Web Performance</a></p>
				<p><a href="#">Hosting Matchup</a></p>
			</div>
			<div class="col-sm-10 border-left" id="a2-optimized-serverperformance">
				<div class="row padding-bottom">
					<div class="col-sm-12">
						<a class="btn cta-btn-green" @click="pageSpeedCheck()">Run Check</a> <span class="last-check">Last Check: {{ last_check_date }}</span>
					</div>
				</div>
				<div class="row">
					<div class="col-sm-4"> <!-- using graphs.ttfb -->
						<div id="graph-ttfb" class="box-element normal graph-card wave-bg" :class="graphs.ttfb.color_class">
							<info-button metric='ttfb'></info-button>
							<div class="graph_data">
								<div class="row">
									<div class="col-sm-8">
										<h4>{{graphs.ttfb.display_text}}</h4>
										<p>{{graphs.ttfb.metric_text}}</p>
									</div>
								</div>
								<div class="row">
									<div class="col-sm-6">
										<div class="circle" id="circles-ttfb"></div>
									</div>
									<div class="col-sm-6">
										<span class="glyphicon" :class="['glyphicon-arrow-' + graphs.ttfb.last_check_dir, graphs.ttfb.color_class]" style="font-size: 2em;"></span>
										<br>
										<span :class="graphs.ttfb.color_class">{{graphs.ttfb.last_check_percent}}</span>
										<span>vs <br> last check</span>
									</div>
								</div>
							</div>
							<div class="graph_info" style="display:none;">
								<div class="row">
									<div class="col-sm-12">
										<h4>{{graphs.ttfb.display_text}}</h4>
										<p>{{ graphs.ttfb.explanation}}</p>
									</div>
								</div>
							</div>
							<div class="row graph-card_bottom" style="min-height: 50px;">
								&nbsp;
							</div>
						</div>
						<div id="graph-lcp" class="box-element normal graph-card" :class="graphs.lcp.color_class">
							<info-button metric='lcp'></info-button>
							<div class="graph_data">
								<div class="row">
									<div class="col-sm-10">
										<h4>{{graphs.lcp.display_text}}</h4>
										<p>{{graphs.lcp.metric_text}}</p>
									</div>
								</div>
								<div class="row">
									<div class="col-sm-10">
										<div class="circle" id="circles-lcp"></div>
										<div class="line-graph" id="line-graph-lcp"></div>
									</div>
								</div>
								<div class="row">
									<graph-legend metric='lcp'></graph-legend>
								</div>
							</div>
							<div class="graph_info" style="display:none;">
								<div class="row">
									<div class="col-sm-10">
										<h4>{{graphs.lcp.display_text}}</h4>
										<p>{{ graphs.lcp.explanation}}</p>
									</div>
								</div>
							</div>
						</div>
						<div id="graph-fid" class="box-element normal graph-card" :class="graphs.fid.color_class">
							<info-button metric='fid'></info-button>
							<div class="graph_data">
								<div class="row">
									<div class="col-sm-12">
										<h4>{{graphs.fid.display_text}}</h4>
										<p>{{graphs.fid.metric_text}}</p>
									</div>
								</div>
								<div class="row">
									<div class="col-sm-10">
										<div class="circle" id="circles-fid"></div>
										<div class="line-graph" id="line-graph-fid"></div>
									</div>
								</div>
								<div class="row">
									<graph-legend metric='fid'></graph-legend>
								</div>
							</div>
							<div class="graph_info" style="display:none;">
								<div class="row">
									<div class="col-sm-12">
										<h4>{{graphs.fid.display_text}}</h4>
										<p>{{ graphs.fid.explanation}}</p>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="col-sm-4 bg-green">
						<div id="graph-overall_score" class="box-element normal graph-card-centered" :class="graphs.overall_score.color_class">
							<info-button metric='overall_score'></info-button>
							<div class="graph_data">
								<div class="row">
									<div class="col-sm-12">
										<h4>{{graphs.overall_score.display_text}}</h4>
										<p>{{graphs.overall_score.metric_text}}</p>
									</div>
								</div>
								<div class="row">
									<div class="col-sm-12">
										<div class="circle" id="circles-overall_score"></div>
									</div>
								</div>
								<div class="row">
									<div class="col-sm-12">
										<span class="glyphicon" :class="['glyphicon-arrow-' + graphs.overall_score.last_check_dir,graphs.overall_score.color_class]" style="font-size: 2em;"></span>
										<br>
										<span :class="graphs.overall_score.color_class">{{graphs.overall_score.last_check_percent}}</span>
										<span>vs <br> last check</span>
									</div>
								</div>
							</div>
							<div class="graph_info" style="display:none;">
								<div class="row">
									<div class="col-sm-12">
										<h4>{{graphs.overall_score.display_text}}</h4>
										<p>{{ graphs.overall_score.explanation}}</p>
									</div>
								</div>
							</div>
						</div>
						<div id="graph-Recommendations" class="box-element normal graph-card-centered ">
							<info-button metric='Recommendations'></info-button>
							<div class="graph_data">
								<div class="row">
									<div class="col-sm-12">
										<h4>{{graphs.recommendations.display_text}}</h4>
									</div>
								</div>
								<div class="row text-left" >
									<div class="col-sm-12">
									<ul>
										<li v-for="recommendation in graphs.recommendations.list">{{recommendation.display_text}}</li>
									</ul>
									</div>
								</div>
							</div>
							<div class="graph_info" style="display:none;">
								<div class="row">
									<div class="col-sm-12">
										<h4>{{graphs.recommendations.display_text}}</h4>
										<p>{{ graphs.recommendations.explanation}}</p>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="col-sm-4">
						<div id="graph-fcp" class="box-element normal graph-card" :class="graphs.fcp.color_class" >
							<info-button metric='fcp'></info-button>
							<div class="graph_data">
								<div class="row">
									<div class="col-sm-10">
										<h4>{{graphs.fcp.display_text}}</h4>
										<p>{{graphs.fcp.metric_text}}</p>
									</div>
								</div>
								<div class="row">
									<div class="col-sm-10">
										<div class="circle" id="circles-fcp"></div>
										<div class="line-graph" id="line-graph-fcp"></div>
									</div>
								</div>
								<div class="row">
									<graph-legend metric='fcp'></graph-legend>
								</div>
							</div>
							<div class="graph_info" style="display:none;">
								<div class="row">
									<div class="col-sm-10">
										<h4>{{graphs.fcp.display_text}}</h4>
										<p>{{ graphs.fcp.explanation}}</p>
									</div>
								</div>
							</div>
						</div>
						<div id="graph-cls" class="box-element normal graph-card wave-bg" :class="graphs.cls.color_class">
							<info-button metric='cls'></info-button>
							<div class="graph_data">
								<div class="row">
									<div class="col-sm-10">
										<h4>{{graphs.cls.display_text}}</h4>
										<p>{{graphs.cls.metric_text}}</p>
									</div>
								</div>
								<div class="row">
									<div class="col-sm-6">
										<div class="circle" id="circles-cls"></div>
									</div>
									<div class="col-sm-6">
										<span class="glyphicon" :class="['glyphicon-arrow-' + graphs.cls.last_check_dir,graphs.cls.color_class]" style="font-size: 2em;"></span>
										<br>
										<span :class="graphs.cls.color_class">{{graphs.cls.last_check_percent}}</span>
										<span>vs <br> last check</span>
									</div>
								</div>
								<div class="row">
									<graph-legend metric='cls'></graph-legend>
								</div>
							</div>
							<div class="graph_info" style="display:none;">
								<div class="row">
									<div class="col-sm-12">
										<h4>{{graphs.cls.display_text}}</h4>
										<p>{{ graphs.cls.explanation}}</p>
									</div>
								</div>
							</div>
							<div class="row">
							</div>
							<div class="row graph-card_bottom" style="min-height: 50px;">
								&nbsp;
							</div>
						</div>
						<div class="text-center">
							<a href="#" class="btn btn-lg cta-btn-green text-right">Improve Score</a>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	`,
	props: ['updateChild'],
	watch: {
		updateChild: function () {
			this.drawGraphs();
		}
	},
	mounted() {
		let that = this;
		document.addEventListener("DOMContentLoaded", function () {
			that.drawGraphs();
		});
	}
});

Vue.component('page-speed-score', {
	data() {
		page_data.info_toggle_data = {
			'pagespeed': false,
			'opt': false
		};
		return page_data
	},
	methods: {
		pageSpeedCheck: function () {
			this.$root.pageSpeedCheck('page-speed-score');
		},
		doCircles: function () {
			let graphs = page_data.graphs;
			var plsMobile = generateCircle('circles-pls-mobile', 40, 10, graphs.pagespeed_mobile.overall_score);
			var plsDesktop = generateCircle('circles-pls-desktop', 40, 10, graphs.pagespeed_desktop.overall_score);
			//jQuery('#circles-pls-mobile .circles-text, #circles-pls-desktop .circles-text').css({ 'left': '-32px', 'top': '-10px', 'font-size': '50px' });

			var optPerf = generateCircle('circles-opt-perf', 35, 7, graphs.opt_perf);
			var optSec = generateCircle('circles-opt-sec', 35, 7, graphs.opt_security);
			var optBP = generateCircle('circles-opt-bp', 35, 7, graphs.opt_bp);
		}
	},
	template: `
	<div class="row">
		<div class="col-sm-10 col-sm-offset-1">
			<div class="row">
				<div class="col-sm-6">
					<div id="graph-pagespeed" class="box-element success">
						<info-button metric='pagespeed'></info-button>
						<div class="graph_info" style="display:none;">
							<div class="row header">
								<div class="col-sm-8">
									<h3>Page Load Speed</h3>
								</div>
								<div class="col-sm-4 text-right">
									<p><a class="btn cta-btn-green" @click="pageSpeedCheck('page_speed_score')">Run check</a><br>
									<span>Last Check: {{ last_check_date }}</span></p>
								</div>
							</div>
							<div class="row">
								<div class="col-sm-10 col-sm-offset-1">
									<p>{{ explanations.pagespeed}}</p>
								</div>
							</div>
						</div>
						<div class="graph_data">
							<div class="row header">
								<div class="col-sm-8">
									<h3>Page Load Speed</h3>
								</div>
								<div class="col-sm-4 text-right">
									<p><a class="btn cta-btn-green" @click="pageSpeedCheck('page_speed_score')">Run check</a><br>
									<span>Last Check: {{ last_check_date }}</span></p>
								</div>
							</div>
							<div class="row">
								<div class="col-sm-11 col-sm-offset-1">
									<div v-if="graphs.pagespeed_mobile" class="col-sm-5 box-element" :class="graphs.pagespeed_mobile.overall_score.color_class">
										<p class='box-title'>Mobile</p>
										<div class="circle" id="circles-pls-mobile"></div>
										<div v-if="graphs.pagespeed_mobile.overall_score.last_check_percent != 0">
										<p><span :class="graphs.pagespeed_mobile.overall_score.color_class"><span class="glyphicon" :class="'glyphicon-arrow-' + graphs.pagespeed_mobile.overall_score.last_check_dir" aria-hidden="true"></span>{{ graphs.pagespeed_mobile.overall_score.last_check_percent }}%</span> Since Last Check</p>
										</div>
										<div v-else>
											<p>&nbsp;</p>
										</div>
									</div>
									<div v-if="graphs.pagespeed_desktop" class="col-sm-5 col-sm-offset-1 box-element" :class="graphs.pagespeed_desktop.overall_score.color_class">
										<p class='box-title'>Desktop</p>
										<div class="circle" id="circles-pls-desktop"></div>
										<div v-if="graphs.pagespeed_desktop.overall_score.last_check_percent != 0">
										<p><span :class="graphs.pagespeed_desktop.overall_score.color_class"><span class="glyphicon" :class="'glyphicon-arrow-' + graphs.pagespeed_desktop.overall_score.last_check_dir" aria-hidden="true"></span>{{ graphs.pagespeed_desktop.overall_score.last_check_percent }}%</span> Since Last Check</p>
										</div>
										<div v-else>
											<p>&nbsp;</p>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-sm-7">
							<p>Data pulled from Google PageSpeed Insights</p>
						</div>
						<div class="col-sm-5 text-right">
							<p>Compare with <a href="https://gtmetrix.com/" target="_blank">GTMetrix</a></p>
						</div>
					</div>
				</div>
				<div class="col-sm-6" id="a2-optimized-optstatus">
					<div id="graph-opt" class="box-element success">
						<info-button metric='opt'></info-button>
						<div class="graph_info" style="display:none;">
							<div class="row header">
								<div class="col-sm-12">
									<h3>Optimization Status</h3>
								</div>
							</div>
							<div class="row">
								<div class="col-sm-10 col-sm-offset-1">
									<p>{{ explanations.opt}}</p>
								</div>
							</div>
						</div>
						<div class="graph_data">
							<div class="row header">
								<div class="col-sm-12">
									<h3>Optimization Status</h3>
								</div>
							</div>
							<div class="row">
								<div class="col-sm-11 col-sm-offset-1">
									<div class="col-sm-11 box-element normal">
										<div class="row">
											<div class="col-sm-4 text-center">
												<div class="circle" id="circles-opt-perf"></div>
												Performance
											</div>
											<div class="col-sm-4 text-center">
												<div class="circle" id="circles-opt-sec"></div>
												Security
											</div>
											<div class="col-sm-4 text-center">
												<div class="circle" id="circles-opt-bp"></div>
												Best Practices
											</div>
										</div>
									</div>
								</div>
								<div class="col-sm-10 col-sm-offset-1 text-right">
									<p><a href="#" class="cta-link">Go to Recommendations</a></p>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	`,
	mounted() {
		let that = this;

		document.addEventListener("DOMContentLoaded", function () {
			that.doCircles();
		});
	},
	props: ['updateChild'],
	watch: {
		updateChild: function () {
			this.doCircles();
		}
	}
});

var app = new Vue({
	el: '#a2-optimized-wrapper',
	data: page_data,
	methods: {
		toggleInfoDiv: function (metric) {
			let data_div = document.querySelector('#graph-' + metric + ' .graph_data');
			let explanation_div = document.querySelector('#graph-' + metric + ' .graph_info');

			if (data_div.style.display == 'none') {
				data_div.style.display = '';
				explanation_div.style.display = 'none';
			}
			else {
				data_div.style.display = 'none';
				explanation_div.style.display = '';
			}
		},
		pageSpeedCheck: function (page) {
			var params = new URLSearchParams();
			params.append('action', 'run_benchmarks');
			params.append('a2_page', page);

			axios
				.post(ajaxurl, params)
				.then((response) => {
					console.log('post finished, got data');
					console.log(response.data);
					page_data.last_check_date = 'just now'; // todo: get this the right value
					page_data.graphs = response.data;
					page_data.updateView++;
				});
		}
	},
});


