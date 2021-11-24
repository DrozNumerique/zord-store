document.addEventListener("DOMContentLoaded", function(event) {
	
	var notify = document.getElementById('import-notify');
	var step = document.getElementById('import-step');
	var wait = document.getElementById('import-wait');
	var progress = document.getElementById('import-progress');
	var report = document.getElementById('import-report');
	var form = document.getElementById('import-form');
	var submit = document.getElementById('submit-file-import');
	var reset = document.getElementById('button-file-reset');
	var file = document.getElementById('file-import');
	var label = document.getElementById('label-import'); 
	var stop = document.getElementById('label-stop'); 
	var pid = null;

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
					step.innerHTML = result.message;
					if (result.percent > 3) {
						progress.innerHTML = result.percent + '%';
					}
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
	
	function launchImport(params) {
		if (pid == undefined || pid == null) {
		    invokeZord(params);
	    } else {
	    	label.style.display = 'inline';
	    	stop.style.display = 'none';
	    	killProcess(pid);
	    	pid = null;
	    }
	}
	
	document.addEventListener('activate', function(event) {
		toggleImport(true);
	});
	
	document.addEventListener('deactivate', function(event) {
		toggleImport(false);
	});
	
	document.addEventListener('launch', function(event) {
		launchImport(event.detail);
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
		launchImport({
    		form: this,
	    	upload: (this.file.value !== null && this.file.value !== ''),
			uploading: function() {
		    	setTimeout(checkUpload, 500);
			},
			before: function() {
		    	resetProcess({
					notify:notify,
					step:step,
					progress:progress,
					report:report,
					wait:wait
				}, false);
		    	toggleImport(false);
			},
			after: function() {
		    	toggleImport(!submit.disabled);
	   		},
			success: function(result) {
		    	resetProcess({
					notify:notify,
					step:step,
					progress:progress,
					report:report,
					wait:wait
				}, true);
		   		toggleImport(true);
		   	    label.style.display = 'none';
		   	    stop.style.display = 'inline';
		   		pid = result;
		   		setTimeout(followProcess, 200, {
					process : result,
					offset  : 0,
					period  : 500,
					report  : report,
					step    : step,
					wait    : wait,
					progress: progress,
					stopped: function() {
						if (pid == undefined || pid == null) {
							return true;
						} else {
							return false;
						}
					},
					closed: function() {
				    	pid = null;
						label.style.display = 'inline';
				    	stop.style.display = 'none';
					}
				});
		   	}
	    });
	    return false;
	}, false); 
	
});