<?php 
/**
 * @package Williams_Meerkat_Videowall
 * 
 * $developer_key = 'AI39si5LMnWhFWblvQtYbpmk9TObLBUzG-jiWuF04JX2BZTjhSgEAxKnxMRzERahiXlNvNugjPbViFUCuVnC5mjvCmwbrxKn1w'
 * $username = 'twitterwilliams@gmail.com'
 * $password = 'twittereph'
 */
?>

<?php 
//$time_pre = microtime(true);
$mvw = new MeerkatVideowallHelper($instance, $this);
$mvw->build_videowall();
//$time_post = microtime(true);
//echo $time_post - $time_pre;