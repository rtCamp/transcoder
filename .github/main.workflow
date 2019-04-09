workflow "Deploy" {
  on = "create"
  resolves = ["WordPress Plugin Deploy"]
}

# Filter for tag
action "tag" {
    uses = "actions/bin/filter@master"
    args = "tag"
}

action "WordPress Plugin Deploy" {
  needs = ["tag"]
  uses = "rtCamp/action-wp-org-plugin-deploy@master"
  secrets = ["WORDPRESS_USERNAME", "WORDPRESS_PASSWORD"]
  env = {
    SLUG = "transcoder"
    EXCLUDE_LIST = "deploy.sh deploy-common.sh readme.sh README.md .gitattributes .gitignore map.conf nginx.log tests bin assets CONTRIBUTING.md Gruntfile.js bootstrap_tests.php config.rb log package.json phpunit.xml phpunit.xml.dist phpunit.xml node_modules .jsrc .travis.yml"
  }
}