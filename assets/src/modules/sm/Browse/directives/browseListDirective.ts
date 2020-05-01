angular.module('sm').directive('browseList', function ($http, entityDataRequest) {
  const hierarchy = ['school', 'department', 'course', 'section']
  const capitalize = function (string) {
    return string.charAt(0).toUpperCase() + string.slice(1)
  }

  return {
    restrict: 'A',
    link: {
      pre: function (scope, elm, attrs) {
        const hIndex = hierarchy.indexOf(attrs.browseList)
        if (hIndex === -1) {
          throw 'browseList mode does not exist'
        }
        const itemName = hierarchy[hIndex]
        const childrenName = hierarchy[hIndex + 1] + 's'
        const entityDataRequestMethodName = 'get' + capitalize(childrenName) + 'For' + capitalize(itemName)
        scope[itemName][childrenName] = []
        scope[itemName].ui = {
          expanded: false,
          buttonClass: 'fa-plus',
          toggleDisplay: function () {
            scope[itemName].ui.expanded = !scope[itemName].ui.expanded

            if (scope[itemName].ui.expanded && scope[itemName][childrenName].length === 0) {
              scope[itemName].ui.loading = true
              scope[itemName].ui.buttonClass = 'fa-refresh fa-spin'
              if (itemName === 'course') {
                if (scope.courseCart.contains.course(scope.course)) {
                  let sections = []
                  for (let i = 0; i < scope.state.courses.length; i++) {
                    if (scope.course.id === scope.state.courses[i].id) {
                      sections = scope.state.courses[i].sections
                      break
                    }
                  }
                  if (sections.length > 0) {
                    scope.course.sections = sections
                    scope[itemName].ui.buttonClass = 'fa-minus'
                    return
                  }
                }
              }
              entityDataRequest[entityDataRequestMethodName]({
                term: scope.state.requestOptions.term,
                param: scope[itemName].id
              }).success(function (data, status) {
                if (status === 200 && typeof data.error === 'undefined') {
                  if (data[childrenName].length > 0) {
                    scope[itemName][childrenName] = data[childrenName]
                  } else {
                    scope[itemName].ui.noResults = true
                  }
                } else if (data.error) {
                  // TODO: Better error checking
                  alert(data.msg)
                }
                scope[itemName].ui.buttonClass = 'fa-minus'
              })
            } else if (scope[itemName].ui.expanded) {
              scope[itemName].ui.buttonClass = 'fa-minus'
            } else {
              scope[itemName].ui.buttonClass = 'fa-plus'
            }
          }
        }
      }
    }
  }
})
