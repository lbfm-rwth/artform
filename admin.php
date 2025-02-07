<?php
/* authorize using .htaccess
$valid_passwords = array ("admin" => "admin123");
$valid_users = array_keys($valid_passwords);
$user = isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : '';
$pass = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : '';
$validated = (in_array($user, $valid_users)) && ($pass == $valid_passwords[$user]);
if (!$validated) {
  header('WWW-Authenticate: Basic realm="ArtForm"');
  header('HTTP/1.0 401 Unauthorized');
  die ("Not authorized.");
}
*/
//----------- start authorized -------------
error_reporting(E_ALL);
ini_set('display_errors', 1);

$path = 'forms/';

function isValidFormId($id) {
  global $path;
  return preg_match('/^[0-9a-zA-Z\-\_]{4,50}$/', $id)
   && file_exists($path.$id) && is_dir($path.$id);
}

$action = isset($_POST['action']) ? $_POST['action'] : '';
if ($action == 'save' && isset($_POST['id'])) {
  $id = $_POST['id'];
  if (!preg_match('/^[0-9a-zA-Z\-\_]{4,50}$/', $id))
    die('!Id must have length 4-50 and consists of letters, numbers, "-", ":" and "_".');
  
  $fid = $path.$id;
  if (file_exists($fid))
    die("!Id \"$id\" exists already.");
  
  mkdir($fid);
  $f = fopen("$fid/form.json", "w+");
  fputs($f, $_POST['formdata']);
  fclose($f);
  die($id);
}

if ($action == 'ls') {
  die(json_encode(array_values(array_filter(scandir($path),
    'isValidFormId'))));
}

if ($action == 'archive' && isValidFormId($_POST['id'])) {
  rename($path.$_POST['id'], $path.'_'.$_POST['id']);
}
      
if ($action == 'view' && isset($_POST['id']) 
        && isValidFormId($_POST['id'])) {
  $fid = $path.$_POST['id'];
  $data = [];
  foreach(scandir("$fid") as $fn) {
    if (!is_dir("$fid/$fn") 
          && preg_match('/^[0-9a-zA-Z\-\_]{4,50}+\.dat\.json$/', $fn)) {
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
  body  {background: linear-gradient(to top right, #B65256, #E5C5C0);}
  #ls {margin-bottom:1em;}
  #ls > div {display:inline-block; padding:0.5em; cursor:pointer; background: #11fd; color:white; margin:0.2em; border-radius:0.2em;}
  #ls > div[data-archived=true] {background: #66de;}
  
  #view {background:#fff4; padding:0.2em; border-radius: 0.5em;}
  #view > * {margin:0.5em; }
  #view > span {font-size: 2em; margin: 1em;}
  #view > a[href=""], #view > input[value=""] {display:none;}
  #view > input {width: 20em;}
  #template:not([data-id]),#archive:not([data-id])  {display:none;}
  
 #template {border-radius:0.2em; background: #0aad; border:none; padding:0.5em; color:white;}
 #archive {border-radius:0.2em; background: #a0ad; border:none; padding:0.5em; color:white;}
  
  .btn.clear-all {background: #f00a; color:white;}
  .btn.save-template {background: #090a; color:white;}

  table.dataTable tbody td {white-space: pre-wrap;}
</style>
</head>
<body>
<?php if(file_exists("format_header.html")) include ("format_header.html");?>
<div id="formbuilder"></div>
<hrule />
<div id="ls"></div>
<div id="view">
<span></span>
<a href="" target="_blank">[link]</a>
<input type="text" value="" readonly />
<button id="template">use as template</button>
<button id="archive">archive</button>
<br /><br />
<table class="cell-border compact stripe hover"></table>
</div>

<script>
var dt = null;
const noCols = [{title:''}];

function makeForm(formData, msg)  {
  const nid = prompt("Short Id" + (msg == '' ? '' : "\n"+msg));
  if (!nid) return;
  $.post('', {action: 'save', id: nid, formdata: formData}, function(reply) {
    if (reply.substr(0, 1) == '!')
      makeForm(formData, reply.substr(1)); // try again
    else
      location.href = 'index.php?'+reply;
  });
}

function makeLink(id) {
  return location.href.replace(/admin\.php.*$/, '?' + id);
}


var formBuilder;

const defaultOps = {
  acionButtons: ['save', 'clear'],
  controlOrder: ['header', 'text', 'textarea', 'number', 'date',
  'select', 'checkbox-group', 'radio-group', 'paragraph'],
  disableFields: ['autocomplete', 'button', 'hidden', 'file'],
  disabledActionButtons: ['data'],
  disabledAttrs: ['access'],
  disabledSubtypes: {text: ['password']},
  fields: [{label: "Email", type: "text", subtype: "email", icon: "@",},
    {label: "Name", type: "text", subtype: "text", icon: "[]"}],
  onSave: (evt, formData) => makeForm(formData, '')
};

const path = "<?php echo $path; ?>";

$($ => {
  
  formBuilder = $('#formbuilder').formBuilder(defaultOps);
  
  
  $('#template').click(function() {
    if (!window.confirm('Replace unsaved form?'))  return;
    const id = $(this).attr('data-id');
    $.get(path+id+'/form.json', function(formdata) {
      var ops = defaultOps;
      ops.formData = formdata;
      ops.dataType = 'json';
      formBuilder = $('#formbuilder').empty().formBuilder(ops);
    }, 'text').fail(function() {
      window.alert('Couldnt load form data.');
    });
  });
  $('#archive').click(function() {
    if (!window.confirm('Archive this survey and discard unsaved form?'))  return;
    const id = $(this).attr('data-id');
    $.post('',{action: 'archive', id: id}, function() {
      location.reload();
    });
  });

  dt = $('#view > table').DataTable({columns: noCols});
  
  $.post('', {action: 'ls'}, function(ls) {
    for (var i=0; i<ls.length; ++i) {
     const id = ls[i];
     if (!id || id.length <=1) continue;
     $('<div/>')
     .attr('data-archived', id[0] == '_')
     .text(id)
     .click(function() {
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
         $('#view > a').attr('href', makeLink(id));
         $('#view > input').attr('value', makeLink(id));
         $('#template').attr('data-id', id);
         $('#archive').attr('data-id', (id[0] != '_') ? id: null);
       }, 'json');
     }).appendTo("#ls");
    }
  }, 'json');
});
</script>


</body></html>
