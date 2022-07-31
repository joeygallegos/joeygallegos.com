// return numbers with comma
function commas(val) {
	while (/(\d+)(\d{3})/.test(val.toString())) {
		val = val.toString().replace(/(\d+)(\d{3})/, '$1'+','+'$2');
	}
	return val;
}

// animate the statistic loading
function animateStatistic(id, start, end, duration) {
	
	var div = $('.stat[data-stat-id=' + id + '] .details .num');
	var range = end - start;

	// no timer shorter than 50ms (not really visible any way)
	var minTimer = 50;

	// calc step time to show all intermediate values
	var stepTime = Math.abs(Math.floor(duration / range));
	
	// never go below minTimer
	stepTime = Math.max(stepTime, minTimer);
	
	// get current time and calculate desired end time
	var startTime = new Date().getTime();
	var endTime = startTime + duration;
	var timer = null;

	function run() {
		var now = new Date().getTime();
		var remaining = Math.max((endTime - now) / duration, 0);
		var value = Math.round(end - (remaining * range));
		div.text(commas(value));
		if (value == end) {
			clearInterval(timer);
		}
	}

	timer = setInterval(run, stepTime);
	run();
}

// on pageload
$(window).on('load', function() {

	// scroll reveal
	window.sr = ScrollReveal({
		wait: '3s',
		easing: 'ease-in',
		scale: {
			direction: 'up',
			power: '5%'
		},
		distance: '50px',
		delay: 500
	});
	sr.reveal('.reveal');

	// if page stats exist
	if ($('body').find('#stats').length > 0) {

		// animate the stat for each project statistic
		$('#stats .stat').each(function(index) {
			animateStatistic($(this).data('stat-id'), 0, $(this).data('stat-value'), 2000);
		});
	}

	// if click on close button
	$('button.close-btn').click(function(event) {
		event.preventDefault();

		if ($('body').find('.message-bar').length > 0) {
			$('.message-bar').fadeOut();
		}
	});

	// animate the stat for each project statistic
	if ($('body').find('#wrk-exp ul li').length > 0) {
		$('#wrk-exp ul li').not('.fadein').each(function(index) {
			var self = this;
			var timer = index * 200;

			// each one need to fade in delayed
			setTimeout(function () {
				$(self).addClass('fadein');
				$(self).fadeIn();
			}, timer);
		});
	}
});