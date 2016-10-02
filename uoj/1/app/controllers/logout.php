<?php
	crsf_defend();
	Auth::logout();
?>

<script type="text/javascript">
var prevUrl = document.referrer;
if (!prevUrl) {
  prevUrl = '/';
};
window.location.href = prevUrl;
</script>
