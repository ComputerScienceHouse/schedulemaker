angular.module('sm').directive('professorLookup', function ($http) {
  return {
    restrict: 'A',
    scope: {
      professorLookup: '='
    },
    template: '{{professorLookup}}',
    link: {
      pre: function (scope, elm, attrs) {},
      post: function (scope, elm, attrs) {
        if (scope.professorLookup !== '' && scope.professorLookup !== 'TBA') {
          scope.stats = 'none'
          elm.on('click', function () {
            if (scope.stats === 'none') {
              $http
                .post(
                  '/api/rmp.php',
                  { name: scope.professorLookup },
                  {
                    headers: {
                      'Content-Type': 'application/json'
                    }
                  }
                )
                .success(function (data, status, headers, config) {
                  const results = data.data.search.teachers.edges
                  if (!results[0]) {
                    elm.popover({
                      html: true,
                      trigger: 'manual',
                      placement: 'auto left',
                      title: scope.professorLookup,
                      content: '<a target="_blank" href="https://www.ratemyprofessors.com/search/professors/807?q=' +
                      scope.professorLookup +
                      '">No results on RateMyProfessors.com!</a>'
                    })
                    elm.popover('show')
                    scope.stats = null
                    return
                  }
                  const teacher = results[0].node
                  const ratingColor = function (score) {
                    score = parseFloat(score)
                    if (score >= 4) {
                      return '#18BC9C'
                    } else if (score >= 3) {
                      return '#F39C12'
                    } else {
                      return '#E74C3C'
                    }
                  }
                  scope.stats = {
                    name: teacher.firstName + ' ' + teacher.lastName,
                    url:
                      'https://www.ratemyprofessors.com/professor/' +
                      teacher.legacyId,
                    dept: teacher.department,
                    numRatings: teacher.numRatings,
                    rating: teacher.avgRating,
                    difficulty: teacher.avgDifficulty
                  }
                  const yearNumber = new Date().getFullYear()
                  elm.popover({
                    html: true,
                    trigger: 'manual',
                    placement: 'auto left',
                    title:
                      '<a target="_blank" href="' +
                      scope.stats.url +
                      '">' +
                      scope.stats.name +
                      ' - ' +
                      scope.stats.dept +
                      '</a>',
                    content:
                      '<div class="row"><div class="col-xs-6 rmp-rating"><h2 style="background-color:' +
                      ratingColor(scope.stats.rating) +
                      '">' +
                      scope.stats.rating +
                      '</h2>Average Rating</div><div class="col-xs-6 rmp-rating"><h2 style="background-color:' +
                      ratingColor(scope.stats.easiness) +
                      '">' +
                      scope.stats.difficulty +
                      '</h2>Level of Difficulty</div></div><div style="text-align:center">Based on ' +
                      scope.stats.numRatings +
                      ' ratings<br><a target="_blank" href="https://www.ratemyprofessors.com/search/professors/807?q=' +
                      encodeURIComponent(scope.professorLookup) +
                      `">Not the right professor?</a><br><small>&copy; ${yearNumber} <a target="_blank" href="http://www.ratemyprofessors.com">RateMyProfessors.com</a></small></div>`
                  })
                  elm.popover('show')
                })
            } else {
              elm.popover('toggle')
            }
          })
        }
      }
    }
  }
})
