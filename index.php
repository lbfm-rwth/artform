<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

function isValidFormId($id) {
  return preg_match('/^[0-9a-zA-Z\-\_]{4,20}$/', $id) && file_exists("forms/$id") && is_dir("forms/$id");
}

if ( isset($_POST['action']) && $_POST['action'] == 'submit'
  && isset($_POST['id']) && isValidFormId($_POST['id'])
  && isset($_POST['data']) )  {
  
  $fid = 'forms/'.$_POST['id'];
  $data = $_POST['data'];

  $id = '';
  while ($id == '' || file_exists("$fid/$id.dat.json"))  {
    $id = ''.rand(10**8, 10**9);
  }
  $f = fopen("$fid/$id.dat.json", "w");
  fwrite($f, $data);
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
    html,body {width:100%;}
    form, #msg:not(:empty)  {border-radius: 1em; padding:2em; margin:1em; background:#fff4;
        max-width:40rem; margin:0 auto;
    }
    form, form * {font-size: 1.2rem;}
    
</style>
<body>

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
    }, 'text');
    
    $('#submit').click(function() {
      const data = JSON.stringify($('form').serializeArray());
      $.post('', {action: 'submit', id: id, data: data}, function(reply) {
        $('body > form').remove();
        $('#msg').html(reply);
      });
    });
    
    $('form').on('submit', $ => {return false;});
  }
});
</script>

</body>
</html>
