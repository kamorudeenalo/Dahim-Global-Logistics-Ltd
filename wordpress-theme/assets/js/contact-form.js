/**
 * Dahim — contact form department awareness.
 * The form is shared across every "desk", but the right fields/copy for
 * each department are now real content (managed in wp-admin under
 * Departments), not hardcoded here. This reads that data from a JSON
 * script tag rendered by page-contact.php and applies it.
 */
(function () {
	var departmentField = document.getElementById( 'department' );
	var dataEl            = document.getElementById( 'dahim-department-data' );
	if ( ! departmentField || ! dataEl ) return;

	var copy = {};
	try {
		copy = JSON.parse( dataEl.textContent || '{}' );
	} catch ( e ) {
		copy = {};
	}

	var formEyebrow  = document.getElementById( 'form-eyebrow' );
	var formHeading  = document.getElementById( 'form-heading' );
	var companyField = document.getElementById( 'company-field' );
	var serviceField = document.getElementById( 'service-field' );
	var serviceSelect = document.getElementById( 'service' );
	var roleCvRow    = document.getElementById( 'role-cv-row' );
	var roleInput    = document.getElementById( 'role' );
	var cvInput      = document.getElementById( 'cv_link' );
	var messageLabel = document.getElementById( 'message-label' );
	var messageInput = document.getElementById( 'message' );
	var submitBtn    = document.getElementById( 'form-submit' );

	var fallback = {
		eyebrow: 'Get In Touch', heading: 'How can we help?',
		showCompany: true, showService: false, showRoleCv: false,
		label: 'Your Message', placeholder: 'How can we help?', submitLabel: 'Send Message'
	};

	function toggle( el, show ) {
		if ( el ) el.style.display = show ? '' : 'none';
	}

	function applyDepartment() {
		var setting = copy[ departmentField.value ] || fallback;

		if ( formEyebrow ) formEyebrow.textContent = setting.eyebrow;
		if ( formHeading ) formHeading.textContent = setting.heading;

		toggle( companyField, setting.showCompany );

		toggle( serviceField, setting.showService );
		if ( serviceSelect ) serviceSelect.disabled = ! setting.showService;

		toggle( roleCvRow, setting.showRoleCv );
		if ( roleInput ) roleInput.disabled = ! setting.showRoleCv;
		if ( cvInput ) cvInput.disabled = ! setting.showRoleCv;

		if ( messageLabel ) messageLabel.textContent = setting.label;
		if ( messageInput ) messageInput.placeholder = setting.placeholder;

		if ( submitBtn ) submitBtn.textContent = setting.submitLabel;
	}

	departmentField.addEventListener( 'change', applyDepartment );
	applyDepartment(); // run once on load in case a desk link pre-selected a department
})();
