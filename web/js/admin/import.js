var initialized;
var notify;
var step;
var wait;
var progress;
var report;
var form;
var submit;
var file;
var label; 
var stop; 
var offset;
var pid;

function initImport() {
	if (initialized == undefined) {
		notify = document.getElementById('import-notify');
		step = document.getElementById('import-step');
		wait = document.getElementById('import-wait');
		progress = document.getElementById('import-progress');
		report = document.getElementById('import-report');
		form = document.getElementById('import-form');
		submit = document.getElementById('submit-file-import');
		file = document.getElementById('file-import');
		label = document.getElementById('label-import'); 
		stop = document.getElementById('label-stop'); 
		offset = 0;
		pid = null;
		initialized = true;
	}
}

function toggleImport(activate) {
	if (activate) {
		file.classList.add('admin-input-file-valued');
		submit.parentNode.classList.add('admin-input-file-button-enabled');
		submit.disabled = false;
	} else {
		file.classList.remove('admin-input-file-valued');
		submit.parentNode.classList.remove('admin-input-file-button-enabled');
		submit.disabled = true;
	}
}

document.addEventListener("DOMContentLoaded", function(event) {
	
	initImport();
	
	function reportLine(style, indent, message, newline) {
		span = document.createElement("span");
		span.classList.add(style);
		span.style.paddingLeft = (indent * 2) + "em";
		span.innerHTML = message;
		report.appendChild(span);
		if (newline) {
			var br = document.createElement("br");
			report.appendChild(br);
		}
		report.scrollTop = report.scrollHeight - report.clientHeight;
	}
	
	function resetNotify(displayReport) {
		step.innerHTML = '&nbsp;';
		progress.style.width = '0%';
		progress.innerHTML = '';
		notify.style.display = 'block';
		if (displayReport) {
			elements = report.querySelectorAll('span,br');
			if (elements) {
				[].forEach.call(elements, function(element) {
					element.parentNode.removeChild(element);
				});
			}
    		report.style.display = 'block';
    		wait.style.display = 'block';
    		offset = 0;
		} else {
			wait.style.display = 'none';
    		report.style.display = 'none';
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
							step.innerHTML = PORTAL.locales[LANG].process.wait;
							wait.style.display = 'block';
						}, 200);
					}
				}
			}
		);
	}

	function checkAction() {
		if (pid == undefined || pid == null) {
			if (wait.style.display == 'block') {
				wait.style.display = 'none';
				reportLine('info',  0, '',     true);
				reportLine('error', 0, PORTAL.locales[LANG].process.stopped, true);
				reportLine('info',  0, '',     true);
			}
			return;
		}
		checkProcess(pid, offset, function(result) {
			if (result.error !== undefined) {
				alert(result.error);
			} else {
				progress.style = 'width:' + result.progress + '%;';
				if (result.progress > 3) {
					progress.innerHTML = result.progress + '%';
				}
				if (result.step == 'closed') {
					step.innerHTML = PORTAL.locales[LANG].process.closed;
				} else if (result.step == 'init') {
					step.innerHTML = PORTAL.locales[LANG].process.init;
				} else {
					step.innerHTML = result.step;
				}
				[].forEach.call(result.report, function(line) {
					reportLine(line.style, line.indent, line.message, line.newline);
					offset++;
				});
				if (result.step !== 'closed') {
					setTimeout(checkAction, 500);
				} else {
					label.style.display = 'inline';
			    	stop.style.display = 'none';
					wait.style.display = 'none';
					reportLine('info', 0, '', true);
				}
			}
		});
	}
	
	file.addEventListener("change", function(event) {
		this.firstElementChild.innerText = document.getElementById(this.getAttribute('for')).files[0].name;
		toggleImport(true);
	});
	
	form.addEventListener("submit", function(event) {
	    event.preventDefault();
	    if (pid == undefined || pid == null) {
	    	invokeZord({
	    		form: this,
	    		upload: (this.file.value !== null && this.file.value !== ''),
	    		uploading: function() {
			    	resetNotify(false);
			    	toggleImport(false);
			    	setTimeout(checkUpload, 500);
	    		},
	    		success: function(result) {
	    			resetNotify(true);
	    			toggleImport(true);
	    	    	label.style.display = 'none';
	    	    	stop.style.display = 'inline';
	    			pid = result;
			    	checkAction();
	    		}
	    	});
	    } else {
	    	label.style.display = 'inline';
	    	stop.style.display = 'none';
	    	killProcess(pid);
	    	pid = null;
	    }
	    return false;
	}, false); 
	
});