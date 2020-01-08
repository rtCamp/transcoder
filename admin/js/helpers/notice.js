import { Notice } from '@wordpress/components';

const showNotice = ( type, message, isDismissible = true ) => {
	return (
		<Notice status={ type } isDismissible={ isDismissible }>
		{ message }
		</Notice>
	);
};

export default showNotice;
