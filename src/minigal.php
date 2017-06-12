<?php
/*
	Author: Dave McColl

	licensing: this code is released under the BSD license. You may alter and
	use it in any way that you deem fit, except that you may not claim that it
	is yours  (:

	CHANGELOG:


	Tue May  3 12:56:43 SAST 2005
	-----------------------------
	* enabled image caching, as per a request. Set "cache_thumbs" to 1 in 
		your minigal_conf.php, or specify it on the url.
		thumbnail cachine requires that the script has write permissions 
		to either the current dir (to create a thumbs dir) or to a pre-existing
		thumbs dir in the current dir. If you don't have one of these scenarios,
		or are not willing to provide write access for the process running
		minigal, then don't use cached thumbnails.

	Wed Apr 20 12:10:04 SAST 2005
	-----------------------------
	* fixed a requirement to not have to need tips and texts.
	* fixed bug with tooltip display: if there was an image with a tip, 
		followed by one without, the tip remained. This fix and the one
		above require the latest version of tooltips.js
*/
class Minigal {
	var $selfcontained;
	var $options;
	var $pic_count;
	var $img_h;
	var	$use_tips;
	
	function Minigal ($arr_opts=array()) {/*<<<*/
		if (array_key_exists("selfcontained", $arr_opts)) {
			if ($arr_opts["selfcontained"]) {
				$this->selfcontained=1;
			}
		}
		$opts=array(
			"title"		=> "Image gallery", 
			"dir"		=> ".",
			"thumb_l"	=> 100,
			"disp_l"	=> "",
			"timg"		=> "",
			"css"		=> "",
			"thumbs_w"	=> 200,
			"thumbs_h"	=> 450,
			"thumbs_top"=> 10,
			"heading"	=> "Gallery",
			"show_fnames"=> 1,
			"selected"	=> "",
			"disp_top"	=> "",
			"thumbs_l"	=> 20,
			"thumb_title_height" => 18,
			"tip_delay"	=>	"5000",
			"cache_thumbs" => 0,
		);
		foreach ($opts as $idx => $dval) {
			$this->set_or_default($idx, $arr_opts, $dval);
		}
		if ($this->options["selected"] == "") {
			$this->options["selected"] = 
				$this->get_first_image($this->options["dir"]);
		}
		if ($this->options["disp_top"] == "") {
			$this->options["disp_top"] == $this->options["thumbs_top"];
		}
		if ($this->options["heading"] != "") {
			$this->options["thumbs_top"]+=50;
		}
		if (file_exists("minigal_conf.php")) {
			include_once("minigal_conf.php");
			if (is_array($options)) {
				foreach ($options as $idx => $val) {
					$this->options[$idx] = $val;
				}
			}
			if (is_array($texts)) {
				foreach ($texts as $idx => $val) {
					$this->texts[$idx] = $val;
				}
			}
			if (is_array($titles)) {
				foreach ($this->texts as $idx => $val) {
					if (array_key_exists($idx, $titles)) {
						$this->titles[$idx]=$titles[$idx];
					} else {
						$this->titles[$idx]="About this image:";
					}
				}
			} else {
				if (is_array($this->texts)) {
					foreach ($this->texts as $idx => $val) {
						$this->titles[$idx]="About this image:";
					}
				}
			}
		}
		if ($this->options["dir"] != ".") {
			if (file_exists($this->options["dir"]."/minigal_conf.php")) {
				// allow gallery-specific overrides for each dir
				include_once($this->options["dir"]."/minigal_conf.php");
				if (is_array($options)) {
					foreach ($options as $idx => $val) {
						$this->options[$idx] = $val;
					}
				}
				if (is_array($texts)) {
					foreach ($texts as $idx => $val) {
						$this->texts[$idx] = $val;
					}
				}
				if (is_array($titles)) {
					foreach ($this->texts as $idx => $val) {
						if (array_key_exists($idx, $titles)) {
							$this->titles[$idx]=$titles[$idx];
						} else {
							$this->titles[$idx]="About this image:";
						}
					}
				} else {
					if (is_array($this->texts)) {
						foreach ($this->texts as $idx => $val) {
							$this->titles[$idx]="About this image:";
						}
					}
					
				}
			}
		}
		if ($this->options["cache_thumbs"]) {
			if (!file_exists("thumbs")) {
				mkdir("thumbs");
			}
		}
	}
/*>>>*/
	function file_ext($fname) {/*<<<*/
		$sname=explode(".", $fname);
		return strtolower($sname[count($sname)-1]);
	}
/*>>>*/
	function is_image($fname) {/*<<<*/
		if (in_array($this->file_ext($fname), array(
			"jpg",
			"png",
			"jpeg",
			"jpe",
			"bmp",
			"gif",
		))) {
				return true;
		} else {
			return false;
		}
	}
/*>>>*/
	function get_first_image($dir) {/*<<<*/
		foreach (glob($dir."/*") as $f) {
			if ($this->is_image($f)) {
				return $f;
			}
		}
	}
/*>>>*/
	function render_thumbs() {/*<<<*/
		$this->aimg=array();
		?>
<div class="thumbs" id="thumbs">
	<table border="0" cellpadding="2" cellspacing="2">
		<?php
			if (file_exists($this->options["dir"])) {
				foreach (glob($this->options["dir"]."/*") as $f) {
					if ($this->is_image($f)) {
						$img_names[]=$f;
						if ($f == $this->options["selected"]) {
							$classname="sel_nohi";
						} else {
							$classname="nosel_nohi";
						}
						$bf=basename($f);
						$cthumb = "thumbs/thumb-".$bf.".png";
						if (file_exists($cthumb)) {
							if (filectime($f) <= filectime($cthumb)) {
								$thumb_url = $cthumb;
							} else {
							// forec thumbnail regeneration
								$thumb_url="http://".$_SERVER["HTTP_HOST"]
								.$_SERVER["PHP_SELF"]."?timg=".urlencode($f)
								."&thumb_l=".$this->options["thumb_l"];
							}
						} else {
							$thumb_url="http://".$_SERVER["HTTP_HOST"]
							.$_SERVER["PHP_SELF"]."?timg=".urlencode($f)
							."&thumb_l=".$this->options["thumb_l"];
						}
						list($thumb_w, $thumb_h) = getimagesize($thumb_url);
						list($img_widths[], $img_heights[]) = getimagesize($f);
						$this->aimg[]=$f;
						$this->img_h[]=$thumb_h;
							?>
		<tr><td>
			<img src="<?php print($thumb_url);?>"
						onclick="displayimage(this, '<?php print($f);?>');" 
						onmouseover="this.className=get_hi_class('<?php print($f);?>');"
						onmouseout="this.className=get_norm_class('<?php print($f);?>');"
						class="<?php print($classname);?>"
						id="<?php print($f);?>">
						<?php
						if ($this->options["show_fnames"]) {
							print("<br>".$bf);
						}
						?>
		</td></tr>
							<?php
					}
				}
			} else {
				print("<tr><td>could not open dir ("
					.$this->options["dir"].")</td></tr>");
			}
		?>
	</table>
</div>
<script language="Javascript">
	
		<?php
		// generate the aimg array (JS) and set sel_picidx to selected image
		$imgidx=0;
		if ($this->use_tips) {
			print("createtipbox();\n");
		}
		foreach ($this->aimg as $img) {
			print("aimg[".$imgidx++."]=\"".$img."\";\n");
			if ($img == $this->options["selected"]) {
				print("sel_picidx = ".($imgidx-1).";\n");
			}
			if ($this->use_tips) {
				if (is_array($this->texts)) {
					$bf=basename($img);
					if (array_key_exists($bf, $this->texts)) {
						print("createtip(\"".$img."\", \"".$this->titles[$bf]."\", \""
							.$this->texts[$bf]."\");\n");
						print("img_titles[".($imgidx-1)."] = \"".$this->texts[$bf]
							."\";\n");
					}
				}
			}
		}
		$imgidx = 0;
		foreach ($this->img_h as $h) {
			print("aimg_h[".$imgidx++."] = ".$h.";\n");
		}
		$imgidx = 0;
		foreach ($img_widths as $w) {
			print("img_widths[".$imgidx++."] = ".$w.";\n");
		}
		$imgidx = 0;
		foreach ($img_heights as $h) {
			print("img_heights[".$imgidx++."] = ".$h.";\n");
		}
		$this->pic_count = $imgidx - 1;
		?>
</script>
		<?php
	}
/*>>>*/
	function render_disp() {/*<<<*/
		if ($this->options["disp_l"] != "") {
			list($img_w, $img_h) = getimagesize($this->options["selected"]);
			if ($img_w > $img_h) {
				$disp_w = $this->options["disp_l"];
				if ($img_w) {
					$disp_h = $img_h * $disp_w / $img_w;
				} else {
					$disp_w = 0;
				}
			} else {
				$disp_h = $this->options["disp_l"];
				if ($img_h) {
					$disp_w = $img_w * $disp_h / $img_h;
				} else {
					$disp_w = 0; // cover potential div-zero?
				}
			}
	?>
	<div class="mainimg">
		<img src="<?php print($this->options["selected"]);?>" id="mainimg" 
			onload="finished_loading_disp();" 
			width="<?php print($disp_w);?>" height="<?php print($disp_h);?>">
	</div>
	<?php
		} else {
	?>
	<div class="mainimg">
		<img src="<?php print($this->options["selected"]);?>" id="mainimg" 
			onload="finished_loading_disp();">
	</div>
	<?php
		}
	}
	/*>>>*/
	function render_scripts() {/*<<<*/
		if (file_exists("tooltips.js")) {
			if (is_array($this->texts)) {
				print("<script language=\"Javascript\" src=\"tooltips.js\">"
					."</script>\n");
				print("<script language=\"Javascript\">\ntipdelay = "
					.$this->options["tip_delay"].";\n</script>");
				$this->use_tips = true;
			} else {
				$this->use_tips = false;
			}
		} else {
			$this->use_tips = false;
		}
	?>
<script language="Javascript">
var selected_image="<?php print($this->options["selected"]);?>";
var sel_picidx=0;
var aimg = new Array();
var aimg_h = new Array();
var img_names=new Array();
var img_title_shown = <?php print($this->options["show_fnames"])?>;
var waiting_for_disp=false;
var img_titles = new Array();
var img_widths = new Array();
var img_heights = new Array();
var maintipneeded = true;
function get_norm_class(imgname) {/*<<<*/
	return (imgname == selected_image)?"sel_nohi":"nosel_nohi";
}
/*>>>*/
function get_hi_class(imgname) {/*<<<*/
	return (imgname == selected_image)?"sel_hi":"nosel_hi";
}
/*>>>*/
function displayimage(oldimg, newurl) {/*<<<*/
	set_status("Getting image...");
	waiting_for_disp=true;
	if (obj=document.getElementById(selected_image)) {
		obj.className="nosel_nohi";
	}
	if (obj=document.getElementById(newurl)) {
		obj.className="sel_hi";
	}
	selected_image = newurl;
	if (document.getElementById("mainimg")) {
		document.getElementById("mainimg").src = newurl;
	}
	// ensure that the sel_picidx is up to date
	for (i=0; i < aimg.length; i++) {
		if (aimg[i] == newurl) {
			sel_picidx = i;
			break;
		}
	}
	set_thumb_div_pos(sel_picidx);
	set_button_states();
}
/*>>>*/
function set_thumb_div_pos(sel_picidx) {/*<<<*/
	scrollby=0;
	for (i=0; i<sel_picidx; i++) {
		scrollby+=aimg_h[i]+8;
		if (img_title_shown) {
			scrollby+=<?php print($this->options["thumb_title_height"]);?>;
		}
	}
	if (obj=document.getElementById("thumbs")) {
		obj.scrollTop=scrollby;
	}
}
/*>>>*/
function gotopic(picidx) {/*<<<*/
	if (picidx < 0) picidx = 0;
	if (picidx > (aimg.length - 1)) picidx = aimg.length - 1;
	obj=document.getElementById(selected_image);
	displayimage(obj, aimg[picidx]);
}
/*>>>*/
function setdisabled(id, disabled) {/*<<<*/
	if (obj=document.getElementById(id)) {
		obj.disabled = disabled;
	}
}
/*>>>*/
function set_button_states() {/*<<<*/
	if (sel_picidx == (aimg.length - 1)) {
		setdisabled("btn_golast", true);
		setdisabled("btn_gonext", true);
		setdisabled("btn_goprev", false);
		setdisabled("btn_gofirst", false);
	}
	if (sel_picidx == 0) {
		setdisabled("btn_gofirst", true);
		setdisabled("btn_goprev", true)
		setdisabled("btn_golast", false);
		setdisabled("btn_gonext", false);
	}
	if ((sel_picidx > 0) && (sel_picidx < (aimg.length-1))) {
		setdisabled("btn_gofirst", false);
		setdisabled("btn_gonext", false);
		setdisabled("btn_goprev", false);
		setdisabled("btn_golast", false);
	}
}
/*>>>*/
function incrpic(incrby) {/*<<<*/
	sel_picidx += incrby;
	gotopic(sel_picidx);
}
/*>>>*/
function set_status(str) {/*<<<*/
	if (obj=document.getElementById("status")) {
		obj.innerHTML=str;
	} else {
		alert("no status bar");
	}
}
/*>>>*/
function finished_loading_disp() {/*<<<*/
	if (waiting_for_disp) {
		<?php	if ($this->options["disp_l"] != "") { ?>
		resize_disp(<?php print($this->options["disp_l"]);?>);
		<?php	} ?>
		set_status("Ready.");
		waiting_for_disp = false;
	}
	<?php	if ($this->use_tips) { ?>
	if (maintipneeded) {
		createtip("mainimg", "", "");
		maintipneeded = false;
	}
	copytip(selected_image, "mainimg");
	<?php	} ?>
}
/*>>>*/
function copytip(srcname, destname) {/*<<<*/
	if (titles[srcname]) {
		title=titles[srcname];
		text=texts[srcname];
		createtip("mainimg", title, text);
	} else {
		deltip("mainimg");
		maintipneeded = true;
	}
}
/*>>>*/
function resize_disp(len) {/*<<<*/
	if (obj=document.getElementById("mainimg")) {
		if (img_widths[sel_picidx] > img_heights[sel_picidx]) {
			// work out scaled height for width
			if (img_widths[sel_picidx]) {
				new_height = len * img_heights[sel_picidx] / img_widths[sel_picidx];
			} else {
				new_height = 0;
			}
			obj.width = len;
			obj.height = new_height;
		} else {
			if (img_heights[sel_picidx]) {
				new_width = len * img_widths[sel_picidx] / img_heights[sel_picidx];
			} else {
				new_width = 0;
			}
			obj.height = len;
			obj.width = new_width;
			
		}
	}
}
/*>>>*/
</script>
	<?php
	}
	/*>>>*/
	function render_css() {/*<<<*/
	?>
<style>
div.thumbs {
	position: 	absolute;
	top:		<?php print($this->options["thumbs_top"]);?>px;
	left:		<?php print($this->options["thumbs_l"]);?>px;
	height:		<?php print($this->options["thumbs_h"]);?>px;
	width:		<?php print($this->options["thumbs_w"]);?>px;
	border:		1px solid #555555;
	overflow:	auto;
}
div.heading {
	position:	absolute;
	top:		<?php print($this->options["disp_top"]);?>px;
	height:		<?php print($this->options["thumbs_top"]-$this->options["disp_top"]-5);?>px;
	left:		<?php print($this->options["thumbs_l"]);?>px;
	border:		outset 2px;
	width:		<?php print($this->options["thumbs_w"]);?>px;
	font-size:	8pt;
}
div.thumbnav {
	position:	absolute;
	left:		<?php print($this->options["thumbs_l"]);?>px;
	border:		outset 2px;
	width:		<?php print($this->options["thumbs_w"]);?>px;
	top:		<?php print($this->options["thumbs_h"]+$this->options["thumbs_top"]+5);?>px;
}
div.status {
	color:		red;
	text-align:	center;
	margin:		auto;
	padding:	3px;
}

div.mainimg {
	position:	absolute;
	left:		<?php print($this->options["thumbs_l"]+$this->options["thumbs_w"]+35);?>px;
	top:		<?php print($this->options["disp_top"]);?>px;
	border:		none;
	margin:		auto;
	padding:	15px;
}
img.sel_nohi {
	border:		1px solid yellow;
}
img.sel_hi {
	border:		1px solid cyan;
}
img.nosel_nohi {
	border:		1px solid black;
}
img.nosel_hi {
	border:		1px solid blue;
}
h3.heading {
	text-align:	center;
}
input[type="button"] {
	border:		1px solid #333333;
}
.tiptable {
	border: 1px solid black;
	padding: 0px;
	margin: 0px;
}
.tiptitle {
	color: black;
	background-color: #4c7fb6;
	font-family: verdana, tomaha, helvetica;
	font-weight: bold
	text-align; center;
	text-decoration: underline;
	padding: 2px;
}
.tiptexttable {
	padding: 0px;
	margin: 0px;
}
.tiptext {
	color: black;
	border: 1px solid black;
	background-color: #ffffe1;
	font-family: verdana, tomaha, helvetica;
	text-align: justify;
	padding: 2px;
}
.tiptd {
	padding: 0px;
}
</style>
	<?php
	}
	/*>>>*/
	function render_nav () {/*<<<*/
	?>
<div class="thumbnav">
	<table border="0" cellspacing="2" cellpadding="2" align="center">
		<tr>
			<td><input type="button" value="|&lt;" onclick="gotopic(0);"
				id="btn_gofirst">
				</td>
			<td><input type="button" value="&lt;&lt;" onclick="incrpic(-1);"
				id="btn_goprev">
				</td>
			<td><input type="button" value="&gt;&gt;" onclick="incrpic(1);"
				id="btn_gonext">
				</td>
			<td><input type="button" value="&gt;|" 
				onclick="gotopic(<?php print($this->pic_count);?>);" id="btn_golast">
				</td>
		</tr>
	</table>
	<div class="status" id="status">Loading thumbnails...</div>
</div>
	<?php
	}
/*>>>*/
	function gen_thumb($path, $longest_side = 120) {/*<<<*/
		if (file_exists($path)) {
			$thumb = dirname($path)."/thumbs/thumb-".basename($path).".png";
			header("Content-type: image/png");
			switch ($this->file_ext($path)) {
				case "png": {
					$im    = imagecreatefrompng($path);
					break;
				}
				case "jpg": 
				case "jpeg":
				case "jpe":	{
					$im = imagecreatefromjpeg($path);
					break;
				}
				case "gif": {
					$im = imagecreatefromgif($path);
					break;
				}
				default: {
					die();
				}
			}
			// some smart sizing
			list($img_w, $img_h) = getimagesize($path);
			if ($img_w > $img_h) {
				// longest side is width: use that to calc thumb size
				$thumb_w = $longest_side;
				if ($img_w) {
					$thumb_h = (int)($longest_side * ($img_h / $img_w));
				} else {
					$thumb_h = 0;
				}
			} else {
				// longest side is height: use that to calc thumb size
				$thumb_h = $longest_side;
				if ($img_h) {
					$thumb_w = (int)($longest_side * ($img_w / $img_h));
				} else {
					$thumb_h = 0;
				}
			}
			$tim = imagecreatetruecolor($thumb_w, $thumb_h);
			imagecopyresized($tim, $im, 0, 0, 0, 0, $thumb_w, 
				$thumb_h, $img_w, $img_h);				
			imagedestroy($im);
			imagepng($tim);
			if ($this->options["cache_thumbs"]) {
				imagepng($tim, $thumb);
			}
			imagedestroy($tim);
			die();
		} else {
			$this->create_error_graphic("could not find $path");
		}
	}
	/*>>>*/
	function create_error_graphic($str) {/*<<<*/
		if ($this->options["debug"]) {
			$this->log("Fatal error: ".$str);
			$this->dump_errors();
		} else {
			$img = imagecreatetruecolor(500, 40);
			$colors["red"] = imagecolorallocate($img, 255, 0, 0);
			$colors["black"] = imagecolorallocate($img, 0, 0, 0);
			$colors["drop"] = imagecolorallocatealpha($img, 0, 0, 0, 64);
			imagefill($img, 0, 0, $colors["red"]);
			imagestring($img, 5, 6, 6, "Fatal error:", $colors["drop"]);
			imagestring($img, 5, 5, 5, "Fatal error:", $colors["black"]);
			imageline($img, 5, 20, 110, 20, $colors["black"]);
			imagestring($img, 5, 16, 22, $str, $colors["drop"]);
			imagestring($img, 5, 15, 21, $str, $colors["black"]);
			
			header("Content: image/png");
			imagepng($img);
			imagedestroy($img);
		}
		die(); // quit with the error
	}
	function set_or_default($idx, $srcarr, $default) {/*<<<*/
		if (array_key_exists($idx, $srcarr)) {
			$this->options[$idx] = $srcarr[$idx];
		} else {
			$this->options[$idx] = $default;
		}
	}
/*>>>*/
	function set_if_there($idx, $srcarr) {/*<<<*/
	}
	/*>>>*/
	function render() {/*<<<*/
		if ($this->options["timg"] != "") {
			$this->gen_thumb($this->options["timg"], $this->options["thumb_l"]);
		} else {
			if ($this->selfcontained) {
				// regular html stuffs
				?>
<html>
<head>
<title><?php print($this->options["title"]);?></title>
<?php
				if ($this->options["css"] != "") {
					print("<link rel=\"Stylesheet\" type=\"text/css\" "
						."href=\"".$this->options["css"]."\">\n");
				}
				if (file_exists("default.css")) {
				// auto-include a default.css
					print("<link rel=\"Stylesheet\" type=\"text/css\" "
						."href=\"default.css\">\n");
				} else {
					print("<!-- no default.css -->");
				}
			}
			// now for the style elements that this calss requires
			$this->render_css();
			$this->render_scripts();
			if ($this->selfcontained) {
				?>
				</head><body>
				<?php
				if ($this->options["heading"] != "") {
					print("<div class=\"heading\">"
						."<h3 class=\"heading\">"
						.$this->options["heading"]."</h3>"
						."</div>");
				}
			}
			$this->render_thumbs();
			$this->render_disp();
			$this->render_nav();
			// lastly, use JS to set button states
			?>
<script language="Javascript">
	set_button_states();
	window.onload=function () {
		set_status('Ready.');
	}
</script>
			<?php
		}
	}
/*>>>*/
}

// check for self-contained mode -- this does a demo (:
if ((basename($_SERVER["SCRIPT_NAME"])) == "minigal.php") {
	$opts=array_slice($_GET, 0);
	$opts["selfcontained"]=1;
	$scgal=new Minigal($opts);
	$scgal->render();
}
?>



