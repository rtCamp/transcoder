#!/usr/bin/env bash

set -ex

######################################################
######################## VARS ########################

function ee() { wo "$@"; }
#####################################################




# Install WPe2e dependency
function install_playwright_package () {

    cd $GITHUB_WORKSPACE/tests/e2e-playwright
    npm install

}

#build packages
function build_package(){
    cd $GITHUB_WORKSPACE/tests/e2e-playwright
    npm run build
}

function install_playwright(){
     cd $GITHUB_WORKSPACE/tests/e2e-playwright
    npx playwright install
}

# Run test for new deployed site
function run_playwright_tests () {
    cd $GITHUB_WORKSPACE/tests/e2e-playwright
    npm run test-e2e:playwright --
}
function maybe_install_node_dep() {

	if [[ -n "$NODE_VERSION" ]]; then

		echo "Setting up $NODE_VERSION"
		NVM_LATEST_VER=$(curl -s "https://api.github.com/repos/nvm-sh/nvm/releases/latest" |
			grep '"tag_name":' |
			sed -E 's/.*"([^"]+)".*/\1/') &&
			curl -fsSL "https://raw.githubusercontent.com/nvm-sh/nvm/$NVM_LATEST_VER/install.sh" | bash
		export NVM_DIR="$([ -z "${XDG_CONFIG_HOME-}" ] && printf %s "${HOME}/.nvm" || printf %s "${XDG_CONFIG_HOME}/nvm")"
		[ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh" # This loads nvm

		nvm install "$NODE_VERSION"
		nvm use "$NODE_VERSION"

		[[ -z "$NPM_VERSION" ]] && NPM_VERSION="latest" || echo ''
		export npm_install=$NPM_VERSION
		curl -fsSL https://www.npmjs.com/install.sh | bash
	fi
}

function main() {
   
    maybe_install_node_dep
    install_playwright_package
    build_package
    install_playwright
    run_playwright_tests
}

main
