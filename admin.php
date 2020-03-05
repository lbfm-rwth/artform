<?php
$valid_passwords = array ("admin" => "admin123");
$valid_users = array_keys($valid_passwords);
$user = isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : '';
$pass = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : '';
$validated = (in_array($user, $valid_users)) && ($pass == $valid_passwords[$user]);
if (!$validated) {
  header('WWW-Authenticate: Basic realm="ArtForm"');
  header('HTTP/1.0 401 Unauthorized');
  die ("Not authorized");
}
//----------- start authorized -------------
error_reporting(E_ALL);
ini_set('display_errors', 1);

function isValidFormId($id) {
  return preg_match('/^[0-9a-zA-Z\-\_]{4,20}$/', $id) && file_exists("forms/$id") && is_dir("forms/$id");
}

$action = isset($_POST['action']) ? $_POST['action'] : '';
if ($action == 'save' && isset($_POST['id'])) {
  $id = $_POST['id'];
  if (!preg_match('/^[0-9a-zA-Z\-\_]{4,20}$/', $id))
    die('!Id must have length 4-20 and consists of letters, numbers, "-", and "_".');
  
  $fid = "forms/$id";
  if (file_exists($fid))
    die('!Id exists already.');
  
  mkdir($fid);
  $f = fopen("$fid/form.json", "w+");
  fputs($f, $_POST['formdata']);
  fclose($f);
  die($id);
}

if ($action == 'ls') {
  die(json_encode(array_values(array_filter(scandir('forms/'), 'isValidFormId'))));
}
      
if ($action == 'view' && isset($_POST['id']) && isValidFormId($_POST['id'])) {
  $fid = 'forms/'.$_POST['id'];
  $data = [];
  foreach(scandir("$fid") as $fn) {
    if (!is_dir("$fid/$fn") && preg_match('/^[0-9]+\.dat\.json$/', $fn)) {
      $data[] = file_get_contents("$fid/$fn");
    }
  }
  die('['.implode($data, ',').']');
}


?>
<html>
<head>
<title>artform [admin]</title>
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
  body  {background: linear-gradient(to top right, #f40, #fa2);}

  #ls {margin-bottom:2em;}
  #ls > div {display:inline-block; padding:0.5em; cursor:pointer; background: #11fd; color:white; margin:0.2em; border-radius:0.2em;}
  
  #view {margin:0.5em; background:#fff4; padding:0.5em; border-radius: 0.5em;}
  #view > span {font-size: 2em; margin: 1em;}
  #view > a:not(:empty) {background: white; padding: 0.5em;}
  
  .btn.clear-all {background: #f00a; color:white;}
  .btn.save-template {background: #090a; color:white;}
</style>
</head>
<body>

<div id="formbuilder"></div>
<hrule />
<div id="ls"></div>
<div id="view">
<span></span><a target="_blank"></a>
<table class="cell-border compact stripe hover"></table>
</div>

<script>
var dt = null;
const noCols = [{title:''}];

function makeForm(formData)  {
  const nid = prompt("Short Id");
  if (!nid) return;
  $.post('', {action: 'save', id: nid, formdata: formData}, function(reply) {
    if (reply.substr(0, 1) == '!')
      makeForm(formData); // try again
    else
      location.href = 'index.php?'+reply;
  });
}

function makeLink(id) {
  return location.href.replace(/admin\.php.*$/, '?' + id);
}

$($ => {
  $('#formbuilder').formBuilder({
    acionButtons: ['save', 'clear'],
    controlOrder: [
    'header', 'email',
    'text', 'textarea',
    'number', 'date',
    'select', 'checkbox-group', 'radio-group',
    'paragraph'
    ],
    disableFields: ['autocomplete', 'button', 'hidden', 'file'],
    disabledActionButtons: ['data'],
    disabledAttrs: ['access'],
    disabledSubtypes: {text: ['password']},
    fields: [{
      label: "Email",
      type: "text",
      subtype: "email",
      icon: "@",
    },{
      label: "Name",
      type: "text",
      subtype: "text",
      icon: "[]",
    }],
    onSave: (evt, formData) => makeForm(formData)
  });
  
  dt = $('#view > table').DataTable({columns: noCols});
  
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
         dt = $('#view > table').empty().DataTable({
            data: data,
            columns: columns.length ? columns : noCols
         });
         $("#view > span").text(id);
         $('#view > a').text(makeLink(id)).attr('href', makeLink(id));
       }, 'json');
     }).appendTo("#ls");
    }
  }, 'json');
});
</script>


</body></html>
