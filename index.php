<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

function isValidFormId($id) {
  return preg_match('/^[0-9a-zA-Z\-\_\:]{4,50}$/', $id) && file_exists("forms/$id") && is_dir("forms/$id");
}

if ( isset($_POST['action']) && $_POST['action'] == 'submit'
  && isset($_POST['id']) && isValidFormId($_POST['id'])
  && isset($_POST['data']) )  {
  
  $fid = 'forms/'.$_POST['id'];
  $data = $_POST['data'];

  $id = ''.date("Y-m-d_H-i-s_").rand(1,1000);
  array_unshift($data, array("name" => "timestamp", "value" => $id));
  $f = fopen("$fid/$id.dat.json", "w");
  fwrite($f, json_encode($data));
  fclose($f);
  
  die('Form data saved.');
}

?>
<html>
<head>
<title>artform</title>
<script
  src="https://code.jquery.com/jquery-3.4.1.min.js"
  integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo="
  crossorigin="anonymous"></script>
<script src="form-builder.min.js"></script>
<script src="form-render.min.js"></script>
<script
  src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.11.2/jquery-ui.min.js"></script>
</head>
<style type="text/css">
    body  {background: linear-gradient(to top right, #f40, #fa2);}
    form, #msg:not(:empty)  {border-radius: 1em; padding:2em; margin:1em; background:#fff4;}
</style>
<body>
<?php if(file_exists("format_header.html")) include ("format_header.html");?>
<form>
<div id="formContent"></div>
<button id="submit">submit</button>
</form>

<div id="msg"></div>

<script>
$($ => {
  const id = (location.search.length > 1) ? location.search.substr(1) : null;
  if (id) {
    $.get('forms/'+id+'/form.json', function(formdata) {
      $("#formContent").formRender({formData:formdata});
    }, 'text').fail(function(){
      $("body > form").html("<h2>There is no survey here. You are on the wrong page.</h2>");
    });
    $('#submit').click(function() {
      const data = $('form').serializeArray();
      $.post('', {action: 'submit', id: id, data: data}, function(reply) {
        $('body > form').remove();
        $('#msg').html(reply);
      });
    });
    
    $('form').on('submit', $ => {return false;});
  }
  else{
    $("body > form").html("<h2>There is no survey here. You are on the wrong page.</h2>");
  }
});
</script>

</body>
</html>
