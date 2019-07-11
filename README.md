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
- Fork and clone the repository
- Copy the `config.example.php` file to `config.php` in `/inc/` or set environment variables as defined in `config.env.php`
- Contact a current maintainer for server/database configs
- If you wish to see images locally, you will also need S3 credentials, either supply your own or reach out

### Run Locally
In order to run locally youre going to need [docker](https://www.docker.com/)

```
docker build -t schedulemaker .
docker run --rm -i -t -p 5000:8080 --name=schedulemaker schedulemaker
```

You can replace `5000` with whatever port you wish to connect locally to. Then visit `http://localhost:5000` in a browser

### Development
- To build js files run `npm run-script build`
- Increment the version number in `package.json` after updating js/css files or ensure all cache cleared
  - Make sure you increment at least the patch number in any PRs that touch Javascript/CSS

## Maintainers

### Current Maintainers
- Devin Matte ([@devinmatte](https://github.com/devinmatte))

#### Past Maintainers
- Ben Grawi ([@bgrawi](https://github.com/bgrawi))
- Benjamin Russell ([@benrr101](https://github.com/benrr101))
