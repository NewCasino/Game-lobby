<?php
include ($_SERVER['DOCUMENT_ROOT']."/classes/upload/upload_class.php");
error_reporting(E_ALL);
ini_set("memory_limit", "64M");
set_time_limit(60);

class Foto_upload extends file_upload {
	
	var $x_size;
	var $y_size;
	var $x_max_size = 300;
	var $y_max_size = 200;
	var $x_max_thumb_size = 110;
	var $y_max_thumb_size = 88;
	var $thumb_folder;
	var $foto_folder;
	var $larger_dim;
	var $larger_curr_value;
	var $larger_dim_value;
	var $larger_dim_thumb_value;
	
	var $use_image_magick = true; // switch between true and false
	// I suggest to use ImageMagick on Linux/UNIX systems, it works on windows too, but it's hard to configurate
	// check your existing configuration by your web hosting provider
	
	function process_image($landscape_only = false, $create_thumb = false, $delete_tmp_file = false, $compression = 85) {
		$filename = $this->upload_dir.$this->file_copy;
		$this->check_dir($this->thumb_folder); // run these checks to create not existing directories
		$this->check_dir($this->foto_folder); // the upload dir is created during the file upload (if not already exists)
		$thumb = $this->thumb_folder.$this->file_copy;
		$foto = $this->foto_folder.$this->file_copy;
		if ($landscape_only) {
			$this->get_img_size($filename);
			if ($this->y_size > $this->x_size) {
				$this->img_rotate($filename, $compression);
			}
		}
		$this->check_dimensions($filename); // check which size is longer then the max value
		if ($this->larger_curr_value > $this->larger_dim_value) {
			$this->thumbs($filename, $foto, $this->larger_dim_value, $compression);
		} else {
			copy($filename, $foto);
		}
		if ($create_thumb) {
			if ($this->larger_curr_value > $this->larger_dim_thumb_value) {
				$this->thumbs($filename, $thumb, $this->larger_dim_thumb_value, $compression); // finally resize the image
			} else {
				copy($filename, $thumb);
			}
		}
		if ($delete_tmp_file) $this->del_temp_file($filename); // note if you delete the tmp file the check if a file exists will not work
	}
	function get_img_size($file) {
		$img_size = getimagesize($file);
		$this->x_size = $img_size[0];
		$this->y_size = $img_size[1];
	}
	function check_dimensions($filename) {
		$this->get_img_size($filename);
		$x_check = $this->x_size - $this->x_max_size;
		$y_check = $this->y_size - $this->y_max_size;
		if ($x_check < $y_check) {
			$this->larger_dim = "y";
			$this->larger_curr_value = $this->y_size;
			$this->larger_dim_value = $this->y_max_size;
			$this->larger_dim_thumb_value = $this->y_max_thumb_size;
		} else {
			$this->larger_dim = "x";
			$this->larger_curr_value = $this->x_size;
			$this->larger_dim_value = $this->x_max_size;
			$this->larger_dim_thumb_value = $this->x_max_thumb_size;
		}
	}
	function img_rotate($wr_file, $comp) {
		$new_x = $this->y_size;
		$new_y = $this->x_size;
		if ($this->use_image_magick) {
			exec(sprintf("mogrify -rotate 90 -quality %d %s", $comp, $wr_file));
		} else {
			$src_img = imagecreatefromjpeg($wr_file);
			$rot_img = imagerotate($src_img, 90, 0);
			$new_img = imagecreatetruecolor($new_x, $new_y);
			imageantialias($new_img, TRUE);
			imagecopyresampled($new_img, $rot_img, 0, 0, 0, 0, $new_x, $new_y, $new_x, $new_y);
			imagejpeg($new_img, $this->upload_dir.$this->file_copy, $comp);
		}
	}
	function thumbs($file_name_src, $file_name_dest, $target_size, $quality = 80) {
		//print_r(func_get_args());
		$size = getimagesize($file_name_src);
		if ($this->larger_dim == "x") {
			$w = number_format($target_size, 0, ',', '');
			$h = number_format(($size[1]/$size[0])*$target_size,0,',','');
		} else {
			$h = number_format($target_size, 0, ',', '');
			$w = number_format(($size[0]/$size[1])*$target_size,0,',','');
		}
		if ($this->use_image_magick) {
			exec(sprintf("convert %s -resize %dx%d -quality %d %s", $file_name_src, $w, $h, $quality, $file_name_dest));
		} else {
			$dest = imagecreatetruecolor($w, $h);
			imageantialias($dest, TRUE);
			$src = imagecreatefromjpeg($file_name_src);
			imagecopyresampled($dest, $src, 0, 0, 0, 0, $w, $h, $size[0], $size[1]);
			imagejpeg($dest, $file_name_dest, $quality);
		}
	}
}

$max_size = 1024*1024; // the max. size for uploading (~1MB)
define("MAX_SIZE", $max_size);
$foto_upload = new Foto_upload;

$foto_upload->upload_dir = $_SERVER['DOCUMENT_ROOT']."/test_files/"; // "files" is the folder for the uploaded files (you have to create these folder)
$foto_upload->foto_folder = $_SERVER['DOCUMENT_ROOT']."/test_files/photo/";
$foto_upload->thumb_folder = $_SERVER['DOCUMENT_ROOT']."/test_files/thumb/";
$foto_upload->extensions = array(".jpg"); // specify the allowed extension(s) here
$foto_upload->language = "en";
$foto_upload->x_max_size = 300;
$foto_upload->y_max_size = 200;
$foto_upload->x_max_thumb_size = 120;
$foto_upload->y_max_thumb_size = 150;
		
if (isset($_POST['Submit']) && $_POST['Submit'] == "Upload") {
	$foto_upload->the_temp_file = $_FILES['upload']['tmp_name'];
	$foto_upload->the_file = $_FILES['upload']['name'];
	$foto_upload->http_error = $_FILES['upload']['error'];
	$foto_upload->replace = (isset($_POST['replace'])) ? $_POST['replace'] : "n"; // because only a checked checkboxes is true
	$foto_upload->do_filename_check = "n"; 
	if ($foto_upload->upload()) {
		$foto_upload->process_image(false, true, true, 80);
		$foto_upload->message[] = "Processed foto: ".$foto_upload->file_copy."!"; // "file_copy is the name of the foto"
	}
}
$error = $foto_upload->show_error_string();
?> 
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
"http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<title>Photo-upload form</title>

<style type="text/css">
<!--
body {
	text-align:center;
}
label {
	margin:0;
	float:left;
	display:block;
	width:120px;
}
#main {
	width:350px;
	margin:0 auto;
	padding:20px 0;
	text-align:left;
}
-->
</style>
</head>
<body>
<div id="main">
  <h1>Photo-upload form</h1>
  <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" enctype="multipart/form-data">
	<input type="hidden" name="MAX_FILE_SIZE" value="<?php echo $max_size; ?>"><br>
	<div>
	  <label for="upload">Select a foto</label>
	<input type="file" name="upload" id="upload" size="35"></div>
    <div>
      <label for="replace">Replace an old foto?</label>
    <input type="checkbox" name="replace" value="y"></div>
	<p style="margin-top:25px;text-align:center;"><input type="submit" name="Submit" id="Submit" value="Upload">
	</p>
  </form>
  <p><?php echo $error; ?></p>
</div>  
</body>
</html>