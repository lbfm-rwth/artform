<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

function isValidFormId($id) {
  return preg_match('/^[0-9]+$/', $id) && file_exists($id) && is_dir($id);
}

$action = isset($_POST['action']) ? $_POST['action'] : '';
if ($action == 'save') {
  $id = '';
  while ($id == '' || file_exists($id))  {
    $id = ''.rand(10**8, 10**9);
  }
  
  mkdir($id);
  $f = fopen("$id/form.json", "w+");
  fputs($f, $_POST['formdata']);
  fclose($f);
  
  die($id);
}

if ($action == 'ls') {
  $ls = [];
  foreach(scandir('.') as $id) {
    if (isValidFormId($id)) {
      $ls[] = $id;
    }
  }
  die(json_encode($ls));
}
      
if ($action == 'view' && isValidFormId($_POST['id'])) {
  $id = $_POST['id'];
  $data = [];
  foreach(scandir($id) as $fn) {
    if (!is_dir("$id/$fn") && preg_match('/^[0-9]+\.dat\.json$/', $fn)) {
      $data[] = file_get_contents("$id/$fn");
    }
  }
  die('['.implode($data, ',').']');
}


?>
<html>
<head>
<title>Make form</title>
<script
  src="https://code.jquery.com/jquery-3.4.1.min.js"
  integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo="
  crossorigin="anonymous"></script>
<script src="form-builder.min.js"></script>
<script src="form-render.min.js"></script>
<script
  src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.11.2/jquery-ui.min.js"></script>
<script
  src="https://cdn.datatables.net/1.10.20/js/jquery.dataTables.min.js"></script>
<link rel="stylesheet" type="text/css"
  href="https://cdn.datatables.net/1.10.20/css/jquery.dataTables.min.css" />

<style type="text/css">
  body  {background: linear-gradient(to top right, red, orange);}
  #ls > div {display:inline-block; padding:0.5em; cursor:pointer; background:#44a; color:white; margin:0.2em; border-radius:0.2em;}
  #view {margin-top:2em;}
</style>
</head>
<body>

<div id="formbuilder"></div>
<hrule />
<div id="ls"></div>
<table id="view" class="cell-border compact stripe hover"></table>


<script>
var dt = null;
const noCols = [{title:''}];

$($ => {
  $('#formbuilder').formBuilder({
    acionButtons: ['save', 'clear'],
    controlOrder: [
    'header', 'paragraph',
    'text', 'textarea',
    'number', 'date',
    'select', 'checkbox-group', 'radio-group',
    ],
    disableFields: ['autocomplete', 'button', 'hidden', 'file'],
    disabledActionButtons: ['data'],
    onSave: (evt, formData) => {
      $.post('', {action: 'save', formdata: formData}, function(reply) {
        if (reply != 'ERR')
          location.href = 'index.php?'+reply;
      });
    }
  });
  
  dt = $('#view').DataTable({columns: noCols});
  
  $.post('', {action: 'ls'}, function(ls) {
    for (var i=0; i<ls.length; ++i) {
     const id = ls[i];
     $('<div/>').text(id).click(function() {
       $.post('', {action: 'view', id: id}, function(raw) {
         var data = [], columns = [];
         for (var i = 0; i<raw.length; ++i) {
           data.push([]);
           for (var j = 0; j<raw[i].length; ++j) {
             data[i].push(raw[i][j].value);
             if (i == 0)  columns.push({title: raw[i][j].name});
           }
         }
         if (dt)  dt.destroy();
         dt = $('#view').empty().DataTable({
            data: data,
            columns: columns.length ? columns : noCols
         });
       }, 'json');
     }).appendTo("#ls");
    }
  }, 'json');
});
</script>


</body></html>
