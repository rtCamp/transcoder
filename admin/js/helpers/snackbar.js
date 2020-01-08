import { dispatch } from '@wordpress/data';

const showSnackBar = ( message ) => {
	dispatch('core/notices').createNotice(
		'info',
		message,
		{
			isDismissible: true,
			type: 'snackbar',
		}
	);
};

export default showSnackBar;
