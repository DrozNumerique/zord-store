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
	var start = document.getElementById('label-import'); 
	var stop = document.getElementById('label-stop'); 
	var pid = null;
	var controls = {
		notify:notify,
		step:step,
		progress:progress,
		report:report,
		wait:wait,
		start:start,
		stop:stop
	};
	var follow = {
		period  : 500,
		controls: controls,
		killed  : function() {
			return pid == undefined || pid == null;
		},
		close   : function() {
			pid = null;
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
			resetProcess({controls: {
				start: start,
				stop : stop
			}});
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
		    	resetProcess(controls);
		    	toggleImport(false);
			},
			after: function() {
		    	toggleImport(!submit.disabled);
	   		},
			success: function(result) {
		   		pid = result;
		   		toggleImport(true);
		    	follow = resetProcess(follow, pid);
		   		setTimeout(followProcess(follow), 200);
		   	}
	    });
	    return false;
	}, false); 
	
});