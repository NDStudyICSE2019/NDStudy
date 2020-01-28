$(document).ready(function() {
	$(".showDetailsLast31Days").on('click', function ()
	{
		$('#trackDetailsLast31Days').removeClass('hidden');
		$('#trackDetailsLast31Days').removeAttr('class');
		$('#last31DaysDetails').removeClass('showDetailsLast31Days');
		$('#last31DaysDetails').addClass('hideDetailsLast31Days');
	});
	$(".hideDetailsLast31Days").on('click', function ()
	{
		$('#trackDetailsLast31Days').removeAttr('class');
		$('#trackDetailsLast31Days').addClass('hidden');
		$('#last31DaysDetails').removeClass('hideDetailsLast31Days');
		$('#last31DaysDetails').addClass('showDetailsLast31Days');
	});
	$(".showDetailsLastWeek").on('click', function ()
	{
		$('#trackDetailsLastWeek').removeClass('hidden');
		$('#trackDetailsLastWeek').removeAttr('class');
		$('#lastWeekDetails').removeClass('showDetailsLastWeek');
		$('#lastWeekDetails').addClass('hideDetailsLastWeek');
	});
	$(".hideDetailsLastWeek").on('click', function ()
	{
		$('#trackDetailsLastWeek').removeAttr('class');
		$('#trackDetailsLastWeek').addClass('hidden');
		$('#lastWeekDetails').removeClass('hideDetailsLastWeek');
		$('#lastWeekDetails').addClass('showDetailsLastWeek');
	});
	$(".showDetailsToday").on('click', function ()
	{
		$('#trackDetailsToday').removeClass('hidden');
		$('#trackDetailsToday').removeAttr('class');
		$('#todayDetails').removeClass('showDetailsToday');
		$('#todayDetails').addClass('hideDetailsToday');
	});
	$(".hideDetailsToday").on('click', function ()
	{
		$('#trackDetailsToday').removeAttr('class');
		$('#trackDetailsToday').addClass('hidden');
		$('#todayDetails').removeClass('hideDetailsToday');
		$('#todayDetails').addClass('showDetailsToday');
	});
	$(".showNoTrackDetails").on('click', function ()
	{
		$('#noTrackDetails').removeClass('hidden');
		$('#noTrackDetails').removeAttr('class');
		$('#noTrack').removeClass('showNoTrackDetails');
		$('#noTrack').addClass('hideNoTrackDetails');
	});
	$(".hideNoTrackDetails").on('click', function ()
	{
		$('#noTrackDetails').removeAttr('class');
		$('#noTrackDetails').addClass('hidden');
		$('#noTrack').removeClass('hideNoTrackDetails');
		$('#noTrack').addClass('showNoTrackDetails');
	});
});

