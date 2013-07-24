<!DOCTYPE html>
<html>
    <?php
        require_once("constants.php");
        $ds=ldap_connect($ldap_url);
	if ($ds) { 
            $r=ldap_bind($ds);     // this is an "anonymous" bind, typically
                                                       // read-only access
            $sr=ldap_search($ds, $ldap_dn, $alternate_time_query);
            $info = ldap_get_entries($ds, $sr);
            $rhlocations_array = array();
            $count = $info["count"];
            for($i=0; $i<$count; $i++) {
                if(!in_array($info[$i]["rhatlocation"][0], $rhlocations_array))
                    array_push($rhlocations_array, $info[$i]["rhatlocation"][0]);
            }
            ldap_close($ds);
            asort($rhlocations_array);
        }
    ?>
    <head>
        <link href="//netdna.bootstrapcdn.com/twitter-bootstrap/2.3.2/css/bootstrap-combined.min.css" rel="stylesheet">
        <script src="http://code.jquery.com/jquery-1.10.1.min.js"></script>
        <script src="//netdna.bootstrapcdn.com/twitter-bootstrap/2.3.2/js/bootstrap.min.js"></script>
	<link href="select2.css" rel="stylesheet"/>
	<script src="select2.js"></script>
	<script>
	    $(document).ready(function() {
		$("#e1").select2({
		    placeholder: "Select Red Hat location"
	        });
		$('#push_form').submit(function() {
		    if ($('#new_message').val().length == 0) {
			alert("You need to type in some message!");
		    } else {
			if ($("#e1").select2("val").length == 0) {
			    $.post("post_push_message.php",
				{ message : $('#new_message').val() }
			    )
			    .done(function(data) {
				console.log(data);
				alert(data.response);
			    });
			    
			} else {
			    $.post("post_push_message.php",
				{ message : $('#new_message').val(), location : $("#e1").select2("val") }
			    )
			    .done(function(data) {
				console.log(data);
				alert(data.response);
			    });
			    $("#e1").select2("val", "");
			}
			$('#new_message').val("");
			$('#new_message').focus();
			$('#char_count').text("320 characters remaining");
		    }
		    return false;
		});
		$('#new_message').keydown(function(event) {
		    if (event.which == 13) {
			event.preventDefault();
			$('#submit_push').focus().click();
		    }
		});
		$('#new_message').keyup(function(event) {
		    $('#char_count').text( (320 - this.value.replace(/{.*}/g, '').length) + " characters remaining");
		});
	    });
	</script>
    </head>
    <body>
            <div style="margin-top: 100px; text-align: center; margin-bottom: 20px; width: 100%;">
		<select id="e1" style="min-width: 300px;">
		    <?php
			foreach($rhlocations_array as $rhlocation) {
			    echo "<option value=\"$rhlocation\">$rhlocation</option>";
			}
		    ?>
		</select>
            </div>
	    <div style="position: absolute; left: 50%; margin-left: -235px;">
            <div class="span4 well">
                <form accept-charset="UTF-8" action="" method="POST" id="push_form">
                    <textarea class="span4" maxlength="320" id="new_message" name="new_message" placeholder="Type in your push message" rows="5"></textarea>
                    <h6 class="pull-right" id="char_count">320 characters remaining</h6>
                    <button class="btn btn-info" id="submit_push" type="submit">Push New Message</button>
                </form>
            </div>
	    </div>
    </body>
</html>