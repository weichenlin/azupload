<!DOCTYPE html>
<html lang="zh-TW">
<head>
	<meta charset="utf-8">
	<meta name="description" content="for upload">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>upload.aznc.cc</title>
	<link href="css/bootstrap.min.css" rel="stylesheet" media="screen">
	<style>
		body {
			background: #F7F7F7;
		}
		#container {
			margin: 0 auto;
			max-width: 330px;
		}
		header {
			padding: 0 0 15px 0;
		}
		.history {
			padding: 0 0 0 20px;
		}
		.form-shadow {
		background: #FFFFFF;
	    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
		}
		.up-success {
			color: #333;
			padding-left: 0;
		}
	</style>
	<script src="js/jquery-2.0.2.min.js"></script>
	<script src="js/bootstrap.min.js"></script>
	<script src="js/bootstrap.file-input.js"></script>
	<script>
	var initUploadComponent = function() {

		function humanSize(bytes) {
			const K = 1024;
			const M = K * 1024;
			const G = M * 1024;
			const T = G * 1024;
			
			if (bytes > T) {
				return (Math.round(bytes * 100 / T) / 100) + " TB";
			} else if (bytes > G) {
				return (Math.round(bytes * 100 / G) / 100) + " GB";
			} else if (bytes > M) {
				return (Math.round(bytes * 100 / M) / 100) + " MB";
			} else if (bytes > K) {
				return (Math.round(bytes * 100 / K) / 100) + " KB";
			} else {
				return bytes + " Bytes";
			}
		}
		
		function updateProgressBar(current, total) {
			percent = Math.round((current * 100) / total) ;
			document.querySelector('#upload_progress').style.width = percent + '%';
		}

		function handleSelectUrl(e) {
			var target = document.querySelector('#appendedInputButton');
			target.focus();
			target.select();
		}
		document.querySelector('#select_all_btn').addEventListener('click', handleSelectUrl, false);
		
		function handleProgressInfo(e) {
			if (e.lengthComputable) {
				updateProgressBar(e.loaded, e.total);
		    }
		}

		function handleUploadedInfo(e) {
			updateProgressBar(1, 1);
			
			var data = JSON.parse(this.responseText);
			document.querySelector('#file_size_info').innerHTML = "File size: " + humanSize(data.uploadedSize);
			document.querySelector('#appendedInputButton').value = data.downloadUrl;
			$('#uploadModal').modal('hide');
			$('#finishedModal').modal('show');
			
			var list = document.querySelector('#history_list').innerHTML;
			list = '<li><span class="icon-download"><a class="history" href="' 
				+ data.downloadUrl 
				+ '">' 
				+ data.downloadName 
				+ '</a></span></li>' 
				+ list
				;
			document.querySelector('#history_list').innerHTML = list;
		}

		var xReq;
		function handleStartUpload(e) {
			e.preventDefault();
			if (document.querySelector('#file_input').files.length <= 0) {
				return;
			}
			
			updateProgressBar(0, 1);
			
			xReq = new XMLHttpRequest();
			xReq.upload.addEventListener('progress', handleProgressInfo, false)
			xReq.addEventListener('load', handleUploadedInfo, false);

			upload_form = document.querySelector('#upload_form');
			xReq.open("post", upload_form.action, true);
			xReq.send(new FormData(upload_form));
			
			$('#uploadModal').modal('show');
		}
		document.querySelector('#submit').addEventListener('click', handleStartUpload, false);

		function handleCancelUpload(e) {
			if (xReq) {
				xReq.abort();
			}
		}
		document.querySelector('#cancel_upload_btn').addEventListener('click', handleCancelUpload, false);
	}
	window.addEventListener('load', initUploadComponent, false);
	</script>
</head>

<body>
<div id="container">
	<header class="text-center"><h1>upload.aznc.cc</h1></header>
    <table class="table table-bordered form-shadow">
    	<tr>
        	<td>
            	<form id="upload_form" enctype="multipart/form-data" method="post" action="/?act=ajaxupload">
                	<fieldset>
                    	<label><h4>上傳檔案</h4></label>
                    	<input id="file_input" name="file" type="file">
                    	<input id="submit" class="btn btn-primary btn-small" type="submit" value="upload!">
                    </fieldset>
                </form>
            </td>
        </tr>
        <tr>
        	<td>
            	<h4>歷史紀錄</h4>
                <ul id="history_list" class="unstyled">
<?php foreach($this->histories as $data): ?>
					<li><span class="icon-download"><a class="history" href="<?=$data[ 'url' ]?>"><?=$data[ 'file' ]?></a></span></li>
<?php endforeach; ?>
                </ul>
            </td>
        </tr>
    </table>
    <footer class="text-center">
    	<p class="muted">
        	<small>
        		icon by <a href="http://glyphicons.com/" target="_blank">Glyphicons</a>
        		program by <a href="http://cfarm.blog.aznc.cc/" target="_blank">C瓜</a>
        		visual by <a href="http://azzurro.blog.aznc.cc/" target="_blank">AZZURRO</a>
        	</small>
        </p>
    </footer>
    <!-- 上傳Modal -->
    <div id="uploadModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true"
    	data-backdrop="static" data-keyboard="false">
    	<div class="modal-header">
            <button id="cancel_upload_btn" type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            <h4 id="myModalLabel">上傳中...</h4>
        </div>
        <!--loading bar-->
        <div class="modal-body">
            <div class="progress progress-striped active">
                <div id="upload_progress" class="bar" style="width: 0%;"></div>
            </div>
        </div>
    </div>
    <!-- 成功Modal -->
    <div id="finishedModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    	<div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            <h4 id="myModalLabel">上傳成功！</h4>
        </div>
        <div class="modal-body">
        	<p id="file_size_info" class="up-success">File size: xxx</p>
            <span class="help-inline up-success">Download url:</span>
            <div class="input-append">
                <input class="input-xlarge" id="appendedInputButton" type="text" value="web">
                <button id="select_all_btn" class="btn btn-primary" type="button">選取網址</button>
            </div>
        </div>
    </div>
</div>
</body>
</html>