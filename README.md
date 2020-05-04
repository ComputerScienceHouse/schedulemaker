<div align="center">

CSH Schedule**Maker**
=====================

[![Develop Build Status](https://travis-ci.com/ComputerScienceHouse/schedulemaker.svg?branch=develop)](https://travis-ci.com/ComputerScienceHouse/schedulemaker)
![GPL-2.0](https://img.shields.io/github/license/computersciencehouse/schedulemaker.svg)
![PHP Version](https://img.shields.io/travis/php-v/computersciencehouse/schedulemaker.svg)

A course database lookup tool and schedule building web application for use at Rochester Institute of Technology.
Built, maintained and hosted by [Computer Science House](https://csh.rit.edu)

Available at [schedule.csh.rit.edu](https://schedule.csh.rit.edu)

</div>

## Dev Environment

### Install
1. Fork and clone the repository.
2. In `/inc/` copy the `config.example.php` file or the environment variables section as defined in `config.env.php` to `config.php`.
3. Contact a current maintainer for server/database configs.
4. If you wish to see images locally, you will also need S3 credentials, either supply your own or reach out.

### Usage

In order to run locally youre going to need [docker](https://www.docker.com/).

```
docker build -t schedulemaker .
docker run --rm -i -t -p 5000:8080 --name=schedulemaker schedulemaker
```

You can replace `5000` with whatever port you wish to connect locally to. Then visit `http://localhost:5000` in a browser.

To build js files:
1. Run `npm run-script build`.
2. Increment the version number in `package.json` after updating js/css files or ensure all cache cleared.
  - Make sure you increment at least the patch number in any PRs that touch Javascript/CSS.

### Errors

- If you can't run the `npm run-script build` command
	- Try creating a file named `npm-shrinkwrap.json` with the following contents:
	```
	{
	  "dependencies": {
	    "graceful-fs": {
	        "version": "4.2.2"
	     }
	  }
	}
	```
	- Then run `npm install`
- If your JS/CSS won't load
	- Make sure you are on CSH's network. If not, try contacting a current maintainer.
	- Check that your config file is filled out.

## Contributing

1. [Fork](https://help.github.com/en/articles/fork-a-repo) this repository
    - Optionally create a new [git branch](https://git-scm.com/book/en/v2/Git-Branching-Branches-in-a-Nutshell) if your change is more than a small tweak (`git checkout -b BRANCH-NAME-HERE`)
2. Make your changes locally, commit, and push to your fork
	- Make sure you follow standard php code practices. We use [ESLint](https://eslint.org/docs/rules/) as our linting tool.
3. Create a [Pull Request](https://help.github.com/en/articles/about-pull-requests) on this repo for our Webmasters to review

## Questions/Concerns

Please file an [Issue](https://github.com/ComputerScienceHouse/schedulemaker/issues/new) on this repository.

## Maintainers

### Current Maintainers
- Devin Matte ([@devinmatte](https://github.com/devinmatte))

#### Past Maintainers
- Ben Grawi ([@bgrawi](https://github.com/bgrawi))
- Benjamin Russell ([@benrr101](https://github.com/benrr101))
