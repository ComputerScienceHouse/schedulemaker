angular.module('sm').directive('courseCart', function () {
  return {
    restrict: 'A',
    templateUrl: '/<%=modulePath%>App/templates/cart.min.html'
  }
})
