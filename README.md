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

### Setup
- Fork and clone the repository.
- In `/inc/` copy the `config.example.php` file or the environment variables section as defined in `config.env.php` to `config.php`.
- Contact a current maintainer for server/database configs.
- If you wish to see images locally, you will also need S3 credentials, either supply your own or reach out.

### Run Locally
In order to run locally youre going to need [docker](https://www.docker.com/).

```
docker build -t schedulemaker .
docker run --rm -i -t -p 5000:8080 --name=schedulemaker schedulemaker
```

You can replace `5000` with whatever port you wish to connect locally to. Then visit `http://localhost:5000` in a browser.

### Development
- To build js files run `npm run-script build`.
- Increment the version number in `package.json` after updating js/css files or ensure all cache cleared.
  - Make sure you increment at least the patch number in any PRs that touch Javascript/CSS.
- If you get an error with JS/CSS not loading, check your config file.

### Code Practices
- We use two space indentation

### Issues
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
- If your JS/CSS won't load also make sure you are on CSH's network. If not, try contacting a current maintainer.


## Maintainers

### Current Maintainers
- Devin Matte ([@devinmatte](https://github.com/devinmatte))

#### Past Maintainers
- Ben Grawi ([@bgrawi](https://github.com/bgrawi))
- Benjamin Russell ([@benrr101](https://github.com/benrr101))
