// PHP Layers Menu 3.2.0-rc (C) 2001-2004 Marco Pratesi - http://www.marcopratesi.it/
// Updated 2026 for modern browser compatibility

// Modern browser detection - all modern browsers support DOM
DOM = (document.getElementById) ? 1 : 0;

// Legacy browser detection (kept for compatibility)
NS4 = (document.layers) ? 1 : 0;

// Konqueror detection
Konqueror = (navigator.userAgent.indexOf('Konqueror') > -1) ? 1 : 0;
Konqueror22 = (navigator.userAgent.indexOf('Konqueror 2.2') > -1 || navigator.userAgent.indexOf('Konqueror/2.2') > -1) ? 1 : 0;
Konqueror30 =
	(
		navigator.userAgent.indexOf('Konqueror 3.0') > -1
		|| navigator.userAgent.indexOf('Konqueror/3.0') > -1
		|| navigator.userAgent.indexOf('Konqueror 3;') > -1
		|| navigator.userAgent.indexOf('Konqueror/3;') > -1
		|| navigator.userAgent.indexOf('Konqueror 3)') > -1
		|| navigator.userAgent.indexOf('Konqueror/3)') > -1
	)
	? 1 : 0;
Konqueror31 = (navigator.userAgent.indexOf('Konqueror 3.1') > -1 || navigator.userAgent.indexOf('Konqueror/3.1') > -1) ? 1 : 0;
Konqueror32 = (navigator.userAgent.indexOf('Konqueror 3.2') > -1 || navigator.userAgent.indexOf('Konqueror/3.2') > -1) ? 1 : 0;
Konqueror33 = (navigator.userAgent.indexOf('Konqueror 3.3') > -1 || navigator.userAgent.indexOf('Konqueror/3.3') > -1) ? 1 : 0;

// Opera detection
Opera = (navigator.userAgent.indexOf('Opera') > -1 || navigator.userAgent.indexOf('OPR/') > -1) ? 1 : 0;
Opera5 = (navigator.userAgent.indexOf('Opera 5') > -1 || navigator.userAgent.indexOf('Opera/5') > -1) ? 1 : 0;
Opera6 = (navigator.userAgent.indexOf('Opera 6') > -1 || navigator.userAgent.indexOf('Opera/6') > -1) ? 1 : 0;
Opera56 = Opera5 || Opera6;

// IE detection - modern Edge uses Chromium, old IE used MSIE or Trident
IE = (navigator.userAgent.indexOf('MSIE') > -1 || navigator.userAgent.indexOf('Trident/') > -1) ? 1 : 0;
IE = IE && !Opera;
IE5 = IE && DOM;
IE4 = (document.all && !DOM) ? 1 : 0;

// Modern browser detection
Chrome = (navigator.userAgent.indexOf('Chrome') > -1 && !Opera) ? 1 : 0;
Firefox = (navigator.userAgent.indexOf('Firefox') > -1) ? 1 : 0;
Safari = (navigator.userAgent.indexOf('Safari') > -1 && !Chrome) ? 1 : 0;
Edge = (navigator.userAgent.indexOf('Edg/') > -1) ? 1 : 0;

// For modern browsers, treat them like IE5 (DOM-capable) for menu behavior
if (Chrome || Firefox || Safari || Edge) {
	IE5 = 1;  // This enables the DOM-based code paths
}
