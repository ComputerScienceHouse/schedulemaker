angular.module('sm').factory('globalKbdShortcuts', function ($rootScope) {
    var globalKbdShortcuts = {
        'bindCtrlEnter': function (callback) {
            Mousetrap.bind('mod+enter', function (e) {
                $rootScope.$apply(callback);
                return true;
            });

            // Only allow to bind once, so mock function after first use
            this.bindCtrlEnter = function () {
            };
        },
        'bindEnter': function (callback) {
            Mousetrap.bind('enter', function (e) {
                $rootScope.$apply(callback);
                return true;
            });

            // Only allow to bind once, so mock function after first use
            this.bindCtrlEnter = function () {
            };
        },
        'bindPagination': function (callback) {
            Mousetrap.bind('mod+right', function (e) {
                $rootScope.$apply(callback.apply(e));
                return true;
            });
            Mousetrap.bind('mod+left', function (e) {
                $rootScope.$apply(callback.apply(e));
                return true;
            });

            // Only allow to bind once, so mock function after first use
            this.bindPagination = function () {
            };
        },
        'bindSelectCourses': function (callback) {
            Mousetrap.bind('mod+down', function (e) {
                callback();
                return false;
            });

            // Only allow to bind once, so mock function after first use
            this.bindSelectCourses = function () {
            };
        },
    };
    return globalKbdShortcuts;
});
