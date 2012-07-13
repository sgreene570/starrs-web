// Create button on the main page to start the process of creating a record
$('#create').click(function() {
	$.get($(this).attr('href'),function(data) {
		$('#modal-select-body').html(data);
		$('#modal-select').modal('show');
	});
	return false;
});

$('#createaddress').click(function() {
	$.get("/dns/zonea/create/"+getObjectFromUrl(),function(data) {
		$('#modal-select-body').html(data);
		$('#continue').addClass('hide');
		$('#createrec').removeClass('hide');
		$('#createrec').attr('href',$('#createaddress').attr('href'));
		$('#modal-select').modal('show');
	});
	return false;
});

$('#createtxt').click(function() {
	$.get("/dns/zonetxt/create/"+getObjectFromUrl(),function(data) {
		$('#modal-select-body').html(data);
		$('#continue').addClass('hide');
		$('#createrec').removeClass('hide');
		$('#createrec').attr('href',$('#createtxt').attr('href'));
		$('#modal-select').modal('show');
	});
	return false;
});

// NS
$('#createns').click(function() {
	$.get($(this).attr('href'),function(data) {
		$('#modal-select-body').html(data);
		$('#continue').addClass('hide');
		$('#createrec').removeClass('hide');
		$('#createrec').attr('href',$('#createns').attr('href'));
		$('#modal-select').modal('show');
	});
	return false;
});

// Click after selecting a record type from the dropdown in the popup
$('#continue').click(function() {
	var createUrl = "/dns/"+$('[name=rectype]').val().toLowerCase()+"/create/"+$('[name=address]').val();
	$.get(createUrl,function(data) {
		$('#createrec').attr('href',createUrl);
		$('#modal-select-body').html(data);
		$('#continue').addClass('hide');
		$('#createrec').removeClass('hide');
	});
});

// Close the popup and return classes to their defaults
$('#cancel').click(function() {
	$('#continue').removeClass('hide');
	$('#createrec').addClass('hide');
});

// Click the button to create a record
$('#createrec').click(function() {
	var dataStr = $('#create-form').serialize();
	var url = $('#createrec').attr('href');
	$.post(url,dataStr,function(data) {
		handlePost(data);	
	});
	return false;
});

// Hax for zone
$('#modifyzone .btn-warning').unbind('click');

function getObjectFromUrl() {
	return window.location.pathname.split('/').pop();
}
