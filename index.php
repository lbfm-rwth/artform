<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

function isValidFormId($id) {
  return preg_match('/^[0-9]+$/', $id) && file_exists($id) && is_dir($id);
}

$action = isset($_POST['action']) ? $_POST['action'] : '';
if ($action == 'submit' && isValidFormId($_POST['id']))  {
  $formId = $_POST['id'];
  $data = $_POST['data'];

  $id = '';
  while ($id == '' || file_exists("$formId/$id.dat.json"))  {
    $id = ''.rand(10**8, 10**9);
  }
  $f = fopen("$formId/$id.dat.json", "w");
  fwrite($f, $data);
  fclose($f);
  
  die('Form data saved.');
}

?>


<html>
<head>
<title>Form</title>
<script
  src="https://code.jquery.com/jquery-3.4.1.min.js"
  integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo="
  crossorigin="anonymous"></script>
<script src="form-builder.min.js"></script>
<script src="form-render.min.js"></script>
<script
  src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.11.2/jquery-ui.min.js"></script>
</head>
<body>
  
<form>
<div id="formContent"></div>
<button id="submit">submit</button>
</form>

<script>
$($ => {
  const id = (location.search.length > 1) ? location.search.substr(1) : null;
  if (id) {
    $.get(id+'/form.json', function(formdata) {
      $("#formContent").formRender({formData:formdata});
    }, 'text');
    
    $('#submit').click(function() {
      const data = JSON.stringify($('form').serializeArray());
      $.post('', {action: 'submit', id: id, data: data}, function(reply) {
        $('body').html(reply);
      });
    });
    
    $('form').on('submit', $ => {return false;});
  }
});
</script>

</body>
</html>
