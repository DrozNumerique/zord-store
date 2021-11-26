document.addEventListener("DOMContentLoaded", function(event) {
	
	var form     = document.getElementById('import-form');
	var notify   = document.getElementById('import-notify');
	var step     = document.getElementById('import-step');
	var progress = document.getElementById('import-progress');
	var report   = document.getElementById('import-report');
	var report   = document.getElementById('import-report');
	var wait     = document.getElementById('import-wait');
	var start    = document.getElementById('label-import');
	var stop     = document.getElementById('label-stop');
	var submit   = document.getElementById('submit-file-import');
	var reset    = document.getElementById('button-file-reset');
	var file     = document.getElementById('file-import');
	var follow   = {
		name     : 'import',
		period   : 500,
		controls : {
			notify   : notify,
			step     : step,
			progress : progress,
			report   : report,
			wait     : wait,
			start    : start,
			stop     : stop
		}
	};

	function toggleImport(activate) {
		if (activate) {
			file.classList.add('admin-input-file-valued');
			reset.classList.remove('disabled');
			submit.parentNode.classList.add('admin-input-file-button-enabled');
			submit.disabled = false;
		} else {
			file.classList.remove('admin-input-file-valued');
			reset.classList.add('disabled');
			submit.parentNode.classList.remove('admin-input-file-button-enabled');
			submit.disabled = true;
		}
	}
	
	function checkUpload() {
		invokeZord(
			{
				module:'Portal',
				action:'upload',
				name:'import',
				success: function(result) {
					progress.style = 'width:' + result.percent + '%;';
					progress.innerHTML = result.percent > 3 ? result.percent + '%' : '';
					step.innerHTML = result.message;
					if (result.percent < 100) {
						setTimeout(checkUpload, 500);
					} else {
						setTimeout(function() {
							step.innerHTML = LOCALE.process.wait;
							wait.style.display = 'block';
						}, 200);
					}
				}
			}
		);
	}
	
	document.addEventListener('activate', function(event) {
		toggleImport(true);
	});
	
	document.addEventListener('deactivate', function(event) {
		toggleImport(false);
	});
	
	document.addEventListener('process', function(event) {
		var params = event.detail;
		params.before = function() {
			resetProcess(follow);
			toggleImport(false);
		};
		params.after = function() {
			toggleImport(true);
		};
		handleProcess(event.detail, follow);
	});
	
	file.addEventListener("change", function(event) {
		this.firstElementChild.innerText = document.getElementById(this.getAttribute('for')).files[0].name;
		toggleImport(true);
	});
	
	reset.addEventListener("click", function(event) {
		input = document.getElementById('input-file-import');
		input.value = '';
		input.parentNode.firstElementChild.innerText = INPUT;
		toggleImport(false);
	});
	
	form.addEventListener("submit", function(event) {
	    event.preventDefault();
		handleProcess({
    		form: form,
	    	upload: (form.file.value !== null && form.file.value !== ''),
			uploading: function() {
		    	setTimeout(checkUpload, 500);
			},
			before: function() {
		    	resetProcess(follow);
		    	toggleImport(false);
			},
			after: function() {
		    	toggleImport(true);
	   		}
		}, follow);
	    return false;
	}, false); 
	
});