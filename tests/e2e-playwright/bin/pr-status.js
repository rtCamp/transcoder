#!/usr/bin/env node
// Octokit.js
// https://github.com/octokit/core.js#readme

const { Octokit } = require("@octokit/core");

const octokit = new Octokit({
    auth: process.env.TOKEN,
});

octokit.request("POST /repos/{org}/{repo}/statuses/{sha}", {
    org: "alvitazwar",
    repo: "transcoder",
    sha: process.env.SHA ? process.env.SHA : process.env.COMMIT_SHA,
    state: "success",
    conclusion: "success",
    target_url:
        "https://www.tesults.com/results/rsp/view/status/project/0c8b70a6-8f6c-4b0d-9bfc-e410f6e83568",
    description: "Successfully synced to Tesults",
    context: "E2E Test Result",
});