const WP_ADMIN_USER = {
	username: 'automation',
	password: '@automation00520019',
} as const;

const {
	WP_USERNAME = WP_ADMIN_USER.username,
	WP_PASSWORD = WP_ADMIN_USER.password,
	WP_BASE_URL = 'https://transcoder-test.rt.gw/', //   http://transcoder.com
} = process.env;

export { WP_ADMIN_USER, WP_USERNAME, WP_PASSWORD, WP_BASE_URL };
