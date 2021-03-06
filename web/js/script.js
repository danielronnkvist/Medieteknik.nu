
$(document).ready(function(){
	// create carousel
	$('.carousel').carousel({
		interval: 15000
	});
	// toogle tooltips
	$('[data-toggle="tooltip"]').tooltip();

	// confirm delete
	$('#delete').click(function(event) {
		return confirm("Are you sure you want to delete?");
	});

	// forum report reply
	$('.report a.toggle').click(function(event){
		event.preventDefault();
		$(this).addClass('hidden');
		$(this).siblings('.confirm').removeClass('hidden');
	});

	$('.report a.confirm').click(function(event) {
		event.preventDefault();
		$(this).text('...');

		var post_data = {'postid': $(this).attr('data-id')},
			btn = $(this);

		$.ajax({
			type: 'POST',
			url: BASE_URL + "en/forum/report",
			dataType: 'json',
			data: post_data
		})
		.done(function(data) {
			console.log(data);
			if(data.status == 'success')
			{
				console.log('ok');
				btn.siblings('.thanks').removeClass('hidden');
				btn.addClass('hidden');
			}
		});
	});
})
