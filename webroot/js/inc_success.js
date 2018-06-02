$(document).ready(function() {
    initData();
});

function initData() {
    var tpl_primary = Handlebars.compile($("#form-primary").html());
    var tpl_alts = Handlebars.compile($("#form-alts").html());

    $.ajax({
        async: true,
        url: 'act.php?act=characters',
        mimeType: 'application/json',
        dataType: 'json',
        error: function(xhr, status, error) {
	    window.location = "?act=error_json"
        },
        success: function(json) {
	    if (json['code'] != 0) {
		window.location = "?act=error_json"
		return;
	    }

	    $("#list-primary").append(tpl_primary(json['data']['primary']));
	    characters = json['data']['alts'].sort(function(a, b){
		return a['character_name'].localeCompare(b['character_name']);
	    });
	    for (c in characters) {
		$("#list-alts").append(tpl_alts(characters[c]));
	    }
        },
    });
}


function resetPrimary() {
    btn = '#reset';
    spinButton(btn + '-spin', true);
    colorButton(btn + '-button', 'btn-warning');

    $.ajax({
        async: true,
        url: 'act.php?act=reset',
        mimeType: 'application/json',
        dataType: 'json',
        error: function(xhr, status, error) {
	    spinButton(btn + '-spin', false);
	    colorButton(btn + '-button', 'btn-danger');
	    $(btn + '-pass').html('Error: Request failed: ' + error);
        },
        success: function(json) {
	    spinButton(btn + '-spin', false);
	    if (json['code'] != 0) {
		colorButton(btn + '-button', 'btn-danger');
		$(btn + '-pass').html('Error: [' + json['code'] + '] ' + json['msg']);
	    } else {
		colorButton(btn + '-button', 'btn-success');
		$(btn + '-pass').html(json['data']['pass']);
	    }
        },
    });
}

function toggleAlt(cid) {
    btn = '#toggle-' + cid;
    spinButton(btn + '-spin', true);
    colorButton(btn + '-button', 'btn-warning');

    $.ajax({
        async: true,
        url: 'act.php?act=toggle&character_id=' + cid,
        mimeType: 'application/json',
        dataType: 'json',
        error: function(xhr, status, error) {
	    spinButton(btn + '-spin', false);
	    colorButton(btn + '-button', 'btn-danger');
	    $(btn + '-state').html('Error: Request failed: ' + error);
        },
        success: function(json) {
	    spinButton(btn + '-spin', false);
	    if (json['code'] != 0) {
		$(btn + '-state').html('Error: [' + json['code'] + '] ' + json['msg']);
		colorButton(btn + '-button', 'btn-danger');
	    } else {
		$(btn + "-state").html(json['data']['state']);
		colorButton(btn + '-button', 'btn-success');
	    }
        },
    });
}

function colorButton(btn, color) {
    $(btn).removeClass('btn-default');
    $(btn).removeClass('btn-primary');
    $(btn).removeClass('btn-warning');
    $(btn).removeClass('btn-danger');
    $(btn).removeClass('btn-success');
    $(btn).addClass(color);
}

function spinButton(btn, spin) {
    if (spin) {
	$(btn).addClass("spinning");
	$(btn).removeClass("hide");
    } else {
	$(btn).removeClass("spinning");
	$(btn).addClass("hide");
    }
}
