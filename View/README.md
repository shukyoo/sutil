### This will insert the include file
{{include test/head}}

### You can use the raw php
<?php echo $a; ?>

### This is equal to "<?php echo $hello; ?>"
{{ $hello }}

### This will escape the html tags, equal to "htmlspecialchars($var, ENT_QUOTES | ENT_SUBSTITUTE);"
{{escape $var }}

### Use translation and insert value, used and return the value of "gettext($var)"
{{trans $var }}
