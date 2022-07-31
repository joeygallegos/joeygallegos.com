
var maxAlertsPerMonitoringRecord = 5;
var nowDate = new Date();
var today = new Date(nowDate.getFullYear(), nowDate.getMonth(), nowDate.getDate(), 0, 0, 0, 0);

var yellowColor = "#ffe6b3";
var brightyellowColor = "#FDFF32";
var pinkColor = "#ff9fad";
var whiteColor = "#ffffff";

var toasterNotificationTypeSuccess = "success";
var toasterNotificationTypeInfo = "info";
var toasterNotificationTypeWarning = "warning";
var toasterNotificationTypeError = "error";
var statusReviewed = "Reviewed";
var statusNotReviewed = "Not Reviewed";
var fromAlertHistory = "fromAlertHistory";
var fromAlertDetails = "fromAlertDetails";
var NameChange = "NameChange";
var StatusChange = "StatusChange";
var UCC1 = "UCC1";
var UCC3 = "UCC3";

var MONITORINGGRID = "MG";
var ALERTGRID = "AG";
var MONITORINGQGRID = "MQG";
var INACTIVERECORDGRID = "IG"

var UnResvoledRecordBNSRowIndex = '';
var MonitoringRecordsRowIndex = '';
var monitoringAlertNotesIndex = '';
var Notesflag = '';
var NotesUCCflag = 'UCC';
var AlertStartingRef_DebtorHistory = 'Debtor';
var AlertStartingRef_RecentAlerts = 'RecentAlert';
var MessageID = '';
var ParentRowIndex = '';



var quickViewFullMonitoringView = "Full Monitoring Portfolio";
var quickViewAlertsOnly = "Alerts Only";
var quickViewInActiveRecord = "InActive "
var quickViewDefault = "--Please Select--";

var monitoringRecordsStatusActive = 'Active';
var monitoringRecordsStatusSuspended = 'Suspended';
var monitoringRecordsStatusUnresolved = "Unresolved";
var monitoringRecordsStatusInactive = "InActive";
var monitoringRecordsStatusAll = "All";


/***********************************************  ********************************************/
// monitoring pref begin here

angular.module("MonitoringPreferenceApp", [
    "ngAnimate",
    "ui.bootstrap",
    "toaster",
    "uiSwitch",
    "monitoringPortfolioApp"

]).
    controller("monitoringPreferenceController", ['$scope', '$parse', '$log', '$timeout', 'MonitoringPreferenceAppService', 'busyWaitService', 'notificationService', function ($scope, $parse, $log, $timeout, MonitoringPreferenceAppService, busyWaitService, notificationService) {
        $scope.isDisableMonitoringPref = true;
        $scope.isDisableMonitoringPrefAlerts = true;
        $scope.isManager = false;
        $scope.PreferencesRecord = {};
        $scope.PreferenceObjList = [];
        $scope.BEAlertFileTypesArr = ["NameChange", "StatusChange"];
        $scope.UCCAlertFileTypesArr = ["Original", "Amendment", "Assignment", "Continuation", "Termination", "TaxLien", "Others"];
        $scope.PreferenceCheckboxesArr = ["UseDebtorCityForBus", "UseDebtorCityForInd", "EnableRequiredOrgId", "BEAlertToSubmitter", "UCCAlertToSubmitter"];

        var AuthenticatedUserStatus = MonitoringPreferenceAppService.getAuthenticatedUserStatus();
        AuthenticatedUserStatus.then(function (response) {
            $scope.isUserAuthorized = !response.data;
        });
        $scope.InitializePreferenceValues = function () {
        };
        $scope.preferenceValueConcat = function (prefname, prefvalue) {
            return {
                CustNo: null,
                PrefName: prefname,
                PrefVal: prefvalue,
                LastUpdate: null,
                LastUpdateBy: null
            };

        };

        $scope.GetUserRole = function () {
           MonitoringPreferenceAppService.GetUserRole().then(function (response) {
        var SecurityRoleIndex = 1;
        if (response.data.length > SecurityRoleIndex) {
            var element = document.getElementById('spnOverride');
            var isManager = response.data[SecurityRoleIndex];
            if (isManager === 'Y') {
                $scope.isManager = true;
                $('#btnOverrideRecord').removeClass('hide');
                $('#btnOverrideAlert').removeClass('hide');
            }
        }
        
    });
}


        $scope.isValidEmail = function (emailArr) {          
            var boolean = true;             
           
                angular.forEach(emailArr, function (val, key) {   
                 if (!$scope.emailRegex.test(val) && boolean) {
                    boolean = false;
                }
                });            
            return boolean;
        }

        $scope.AllUccDebtorAlertsCheckboxClicked = function () {
            var checked = $scope.PreferencesRecord.All
            $scope.PreferencesRecord.Original = checked;
            $scope.PreferencesRecord.Amendment = checked;
            $scope.PreferencesRecord.Assignment = checked;
            $scope.PreferencesRecord.Continuation = checked;
            $scope.PreferencesRecord.Termination = checked;
            $scope.PreferencesRecord.TaxLien = checked;
            $scope.PreferencesRecord.Others = checked;
        }

        $scope.RemoveLastComma = function (str) {
            if (str == undefined) return str;
            var strVal = str;
            var lastChar = strVal.slice(-1);
            if (lastChar == ',') {
                strVal = strVal.slice(0, -1);
            }
            return strVal;
        }

        $scope.GetPreferenceNameByKey = function (prefkey) {

            switch (prefkey) {
                case "Others":
                    return "Other";
                case "TaxLien":
                    return "Tax Liens";
                default:
                    return prefkey;
            }
        }
        $scope.GetPreferenceKeyByName = function (prefName) {

            switch (prefName) {
                case "Other":
                    return "Others";
                case "Tax Liens":
                    return "TaxLien";
                default:
                    return prefName;
            }
        }


        $scope.InsertMonitoringPreference = function (PreferenceDetails) {
         
            $scope.PreferenceDetailsForDb = {};

            var busywindow = busyWaitService.Start();
                      
            $scope.emailRegex = /^(?!.*\.{2})([a-zA-Z0-9!#$%&'*+\/=?^_`{|}~-]+([\.][a-zA-Z0-9!#$%&'*+\/=?^_`{|}~-]+)*)@((([\-]?[a-zA-Z0-9]){2,}[\.])*(([a-zA-Z0-9][\-]?))+).(([\.]([a-zA-Z0-9][\-]?){2,}([a-zA-Z0-9])*)+)$/;

            var UnresolveRepEmailBoolean = true;
            var UCCEmailBoolean = true;
            var BEEmailBoolean = true;
            if ($scope.PreferencesRecord.UnresolveRepEmail !== null && $scope.PreferencesRecord.UnresolveRepEmail.length > 0) {
                var UnresolveRepEmailArr = $scope.PreferencesRecord.UnresolveRepEmail.split(",");
                UnresolveRepEmailBoolean = $scope.isValidEmail(UnresolveRepEmailArr);
            }
            if ($scope.PreferencesRecord.UCCEmail !== null && $scope.PreferencesRecord.UCCEmail.length > 0) {
                var UCCEmailArr = $scope.PreferencesRecord.UCCEmail.split(",");
                UCCEmailBoolean = $scope.isValidEmail(UCCEmailArr);
            }
            if ($scope.PreferencesRecord.BEEmail !== null && $scope.PreferencesRecord.BEEmail.length > 0) {
                var BEEmailArr = $scope.PreferencesRecord.BEEmail.split(",");
                BEEmailBoolean = $scope.isValidEmail(BEEmailArr);
            }




            if (!UnresolveRepEmailBoolean || !UCCEmailBoolean || !BEEmailBoolean) {

                busywindow.close();
                notificationService.flash({
                    type: toasterNotificationTypeError, title: "Email Validation Failed", body: "Please enter a valid email address: abc123@email.com,abc123@email.com."
                });

                $timeout(function () { busywindow.close(); }, 5);
                return false;
            }

            $scope.PreferenceObjList = [];

            var BEAlertFileTypesStr = "";
            var UCCAlertFileTypesStr = "";
            angular.forEach(PreferenceDetails, function (val, key) {

                if (val == true || val == "true" || val == "True") {
                    if ($scope.UCCAlertFileTypesArr.indexOf(key) >= 0) {
                        UCCAlertFileTypesStr += $scope.GetPreferenceNameByKey(key) + ",";

                    } else if ($scope.BEAlertFileTypesArr.indexOf(key) >= 0) {
                        BEAlertFileTypesStr += $scope.GetPreferenceNameByKey(key) + ",";

                    } else if ($scope.PreferenceCheckboxesArr.indexOf(key) >= 0) {

                        $scope.PreferenceDetailsForDb[key] = "Y";

                    }
                } else if (val == false || val == "false" || val == "False") {
                    if ($scope.PreferenceCheckboxesArr.indexOf(key) >= 0) {

                        $scope.PreferenceDetailsForDb[key] = "N";

                    }
                }

            });

            BEAlertFileTypesStr = $scope.RemoveLastComma(BEAlertFileTypesStr);
            UCCAlertFileTypesStr = $scope.RemoveLastComma(UCCAlertFileTypesStr);

            $scope.PreferenceDetailsForDb.UnresolveRepEmail = PreferenceDetails.UnresolveRepEmail;
            $scope.PreferenceDetailsForDb.UCCEmail = PreferenceDetails.UCCEmail;
            $scope.PreferenceDetailsForDb.BEEmail = PreferenceDetails.BEEmail;

            $scope.PreferenceDetailsForDb.IgnoreAlertsForPortfolio = PreferenceDetails.IgnoreAlertsForPortfolio;
            $scope.PreferenceDetailsForDb.UnresolveRepFeq = PreferenceDetails.UnresolveRepFeq;
            $scope.PreferenceDetailsForDb.TimeFrameBy = PreferenceDetails.TimeFrameBy;

            $scope.PreferenceDetailsForDb.BEAlertFileTypes = BEAlertFileTypesStr;
            $scope.PreferenceDetailsForDb.UCCAlertFileTypes = UCCAlertFileTypesStr;


            $scope.PreferencesRecord.BEAlertFileTypes = BEAlertFileTypesStr;
            $scope.PreferencesRecord.UCCAlertFileTypes = UCCAlertFileTypesStr;

            angular.forEach($scope.PreferenceDetailsForDb, function (value, key) {

                if ($scope.UCCAlertFileTypesArr.indexOf(key) >= 0) {
                    return;
                } else if ($scope.BEAlertFileTypesArr.indexOf(key) >= 0) {
                    return;
                }
                $scope.PreferenceObjList.push($scope.preferenceValueConcat(key, value));
            });
            // make service request 
            var PreferencesRecordJson = JSON.stringify($scope.PreferenceObjList);

            var dataResults = MonitoringPreferenceAppService.InsertMonitoringPreference(PreferencesRecordJson);
            dataResults.then(function (response) {

                busywindow.close();
                //if sucess
                $scope.isDisableMonitoringPref = true;
                $scope.isDisableMonitoringPrefAlerts = true;
                notificationService.flash({
                    type: toasterNotificationTypeSuccess, title: "Data Updated", body: "Your changes have been saved."
                });
            }, function (response) {
                busywindow.close();
                $scope.isDisableMonitoringPref = false;
                $scope.isDisableMonitoringPrefAlerts = false;
                notificationService.flash({
                    type: toasterNotificationTypeError, title: response.Message, body: response.ExceptionType
                });
            })
        }

        $scope.isDisableMonitoringPreferences = function () {
            $scope.isDisableMonitoringPref = false;
            }
        $scope.isDisableMonitoringPreferencesAlerts = function () {
            $scope.isDisableMonitoringPrefAlerts = false;
           }
        $scope.splitPreferenceFileTypes = function (FileTypes) {
            if (FileTypes == undefined) return;
            var FileTypesArr = FileTypes.split(',');

            angular.forEach(FileTypesArr, function (name, key) {
                var val = $scope.GetPreferenceKeyByName(name);
                $scope.PreferencesRecord[val] = false;
                if (val !== null || val !== "" || val !== undefined) {
                    $scope.PreferencesRecord[val] = true;
                }

            });
        }

        $scope.GetPreferenceValue = function () {
            var busywindow = busyWaitService.Start();
            var GetPreferenceObj = MonitoringPreferenceAppService.GetPreferenceValue();           
            var timeFrameByVal = "Last Month";
            GetPreferenceObj.then(function (response) {
                for (var i = 0; i < response.data.length; i++) {

                    if (response.data[i].PrefName === "UseDebtorCityForPrecisionMatching" || response.data[i].PrefName === "EnableOrgIdAndJuris" || response.data[i].PrefName === "IgnoreUccAlerts") {
                        continue;
                    }
                    if (response.data[i].PrefName === "BEAlertFileTypes" || response.data[i].PrefName === "UCCAlertFileTypes") {
                        $scope.splitPreferenceFileTypes(response.data[i].PrefVal);
                    }
                    if (response.data[i].PrefVal === "Y") { response.data[i].PrefVal = true; } else if (response.data[i].PrefVal === "N") { response.data[i].PrefVal = false; }
                    $scope.PreferencesRecord[response.data[i].PrefName] = response.data[i].PrefVal;
                    if (response.data[i].PrefName === "TimeFrameBy") {
                        timeFrameByVal = response.data[i].PrefVal;
                     
                    }
                }
                $scope.PreferencesRecord.TimeFrameBy = timeFrameByVal;

                $scope.PreferencesRecord.BEAlertFileTypes = "";
                $scope.PreferencesRecord.UCCAlertFileTypes = "";



                busywindow.close();

            }, function (response) {
                busywindow.close();
                notificationService.flash({
                    type: toasterNotificationTypeError, title: response.Message, body: response.ExceptionType
                });
            }
            )
        };
        $scope.GetUserRole();
        $scope.InitializePreferenceValues();
        $scope.GetPreferenceValue();

    }]).directive('afterRender', ['$timeout', function ($timeout) {
        var def = {
            restrict: 'A',
            terminal: true,
            transclude: false,
            link: function (scope, element, attrs) {
                //$timeout(scope.$eval(attrs.afterRender), 0);  
                $('#iLienAppsID,#yourRepresentativeBtn').css("display", "none");
                $('#iLienMyportfolio').css('margin-left', '120px');               
            }
        };
        return def;
    }]);;

angular.module("MonitoringPreferenceApp").
    factory("MonitoringPreferenceAppService", ['$http', function ($http) {
    
        var monitoringApi = {};
        monitoringApi.InsertMonitoringPreference = function (PreferenceDetails) {            
            return $http({            
                method: "POST",
                url: baseUrl() + "Api/MonitoringPreference/InsertPreferencesDetail",
                data: JSON.stringify(PreferenceDetails)            
            });
        };
      monitoringApi.getAuthenticatedUserStatus = function () {
            return $http({
                url: baseUrl() + "Api/MonitoringRecord/IsReadonlyUser"
            });
        };

        monitoringApi.GetPreferenceValue = function () {
            return $http({

                url: baseUrl() + "Api/MonitoringPreference/GetPreferenceRecord"

            });
        };

        monitoringApi.GetUserRole = function (user) {
            return $http({
                url: baseUrl() + "Api/MonitoringPreference/GetCustomMenu",
                params: {
                    user: "user"
                }

            });
        };


        return monitoringApi;

    }]);


// monitoring pref end here
/***********************************************  ********************************************/
angular.module("monitoringPortfolioApp", [
    "ngAnimate",
    "ui.bootstrap",
    "toaster",
    "directivesModule",
    "monitoringPortfolioApp.controllers",
    "monitoringPortfolioApp.services"
]);

var myApp = angular.module("monitoringPortfolioApp.services", []).
    factory("busyWaitService", ['$injector', function ($injector) {
        return {
            Start: function () {
                var $modal = $injector.get("$uibModal");
                var modalInstance = $modal.open({
                    animation: false,
                    backdrop: false,
                    template: "<div class=\"busyWaitWindow2\">Please wait while we process your request...</div>",
                    size: 'loader',
                    controller: function ($scope, $modalInstance) {

                        $scope.ok = function () {
                            $modalInstance.close();
                        };

                        $scope.cancel = function () {
                            $modalInstance.dismiss();
                        };
                    }
                });

                modalInstance.result.then(function () {

                }, function () {

                });
                return modalInstance;
            }
        };


    }]);

angular.module("monitoringPortfolioApp.services")
    .factory("notificationService", ['toaster', function (toaster) {
        return {
            flash: function (message) {
                switch (message.type) {
                    case toasterNotificationTypeSuccess:
                        toaster.pop({
                            type: toasterNotificationTypeSuccess, title: message.title, body: message.body, bodyOutputType: "trustedHtml"
                        });
                        break;
                    case toasterNotificationTypeInfo:
                        toaster.pop({
                            type: toasterNotificationTypeInfo, title: message.title, body: message.body, bodyOutputType: "trustedHtml"
                        });
                        break;
                    case toasterNotificationTypeWarning:
                        toaster.pop({
                            type: toasterNotificationTypeWarning, title: message.title, body: message.body, bodyOutputType: "trustedHtml"
                        });
                        break;
                    case toasterNotificationTypeError:
                        toaster.pop({
                            type: toasterNotificationTypeError, title: message.title, body: message.body, bodyOutputType: "trustedHtml"
                        });
                        break;
                }
            }
        };
    }]);


angular.module("monitoringPortfolioApp.services").
    factory("monitoringPortfolioService", ['$http', function ($http) {
        var monitoringApi = {};

        monitoringApi.ResolveRecords = function (records) {
            return $http({

                url: baseUrl() + "Api/MonitoringRecord/ResolveMonitoringRecords",
                params: {
                    unResolvedrecords: angular.toJson(records)
                }
            });
        };

        monitoringApi.FormPreview = function (option, searchCriteria) {
            return $http({
                responseType: "arraybuffer",
                url: baseUrl() + "Api/MonitoringRecord/Preview",
                params: {
                    alertOption: option,
                    searchOption: angular.toJson(searchCriteria)
                }
            });
        };
        monitoringApi.searchRecords = function (searchCriteria) {
            return $http({
                url: baseUrl() + "Api/MonitoringRecord/Search",
                params: {
                    searchOption: angular.toJson(searchCriteria)
                }
            });
        };

        monitoringApi.getAlertsOnlyDetails = function (alertsOnlyCriteria) { // for all recent alerts
            return $http({
                url: baseUrl() + "Api/MonitoringAlert/AlertSearch",
                params: {
                    recentAlerts99: angular.toJson(alertsOnlyCriteria)
                }
            });
        };
        monitoringApi.getRecentAlertsDetails = function (recentAlerts) { // for all recent alerts
            return $http({
                url: baseUrl() + "Api/MonitoringAlert/GetRecentAlertDetails",
                params: {
                    recentAlerts: angular.toJson(recentAlerts)
                }
            });
        };

        monitoringApi.getAlerts = function (debtor) {
            return $http({
                url: baseUrl() + "Api/MonitoringAlert/GetAlertsByDebtor",
                params: {
                    debtorDetails: angular.toJson(debtor)
                }
            });
        };

        monitoringApi.DeleteUnResolvedRecord = function (monitoringRecord) {            
                return $http({
                    url: baseUrl() + "Api/MonitoringRecord/DeleteUnResolvedRecord",
                    params: {
                        monitoringUnresolvedRecord: angular.toJson(monitoringRecord)

                    }
                });           
        };
        monitoringApi.UpdateMonitoringRecordStatus = function (debtorChangeDetails) {
            return $http({
                url: baseUrl() + "Api/MonitoringRecord/UpdateStatus",
                params: {
                    debtorChangeDetails: angular.toJson(debtorChangeDetails)

                }
            });
        };
        monitoringApi.UpdateMonitoringAlertStatus = function (AlertDetails) {
            return $http({
                url: baseUrl() + "Api/MonitoringAlert/UpdateMonitoringAlertStatus",
                params: {
                    AlertDetails: angular.toJson(AlertDetails)

                }
            });
        };
        monitoringApi.getAuthenticatedUserStatus = function () {
            return $http({
                url: baseUrl() + "Api/MonitoringRecord/IsReadonlyUser"
            });
        };

        monitoringApi.InsertMonitoringNotes = function (MonitoringNotesDetails) {
            return $http({
                url: baseUrl() + "Api/MonitoringRecord/InsertMonitoringNotes",
                params: {
                    inputValues: MonitoringNotesDetails
                }
            });
        };


        monitoringApi.GetCustomMenu = function (user) {
            return $http({
                url: baseUrl() + "Api/MonitoringPreference/GetCustomMenu",
                params: {
                    user: "user"
                }

            });
        }

        monitoringApi.GetPreferenceValue = function () {
            return $http({

                url: baseUrl() + "Api/MonitoringPreference/GetPreferenceRecord"

            });
        };

        return monitoringApi;
    }]);



myApp.factory("httpInterceptor", ['$q', 'notificationService', function ($q, notificationService) {

    return {
        request: function (config) {
            return config;
        },
        response: function (response) {
            return response || $q.when(response);
        },
        responseError: function (reason) {
            var exceptionMessage = 'The WebService: \"' + reason.config.url + '\" failed with Error Code : \"' + reason.status + '\" with a Respose : \"' + reason.statusText + '\"';
            if (reason.data != null) {

                if (reason.status === 401 || reason.status === 403) {
                    window.location.href = sessionExpiredUrl;
                    return;
                }

                notificationService.flash({ type: toasterNotificationTypeError, title: "Error", body: exceptionMessage });
                return $q.reject(reason);
            }
        }
    }
}]);

myApp.config(['$httpProvider', function ($httpProvider) {
    $httpProvider.interceptors.push("httpInterceptor");
}]);

/****************************************** Active Status Service**************************************/


angular.module("monitoringPortfolioApp.services").
    factory("alertHistoryService", ['$http', function ($http) {
        var alertTransactionHistory = {};

        alertTransactionHistory.getTransactHistory = function (alertId) {
            var appUrl = baseUrl();

            return $http({
                url: appUrl + "Api/TransactionHistory/GetTransactionHistoryAlert",
                params: {
                    alertId: alertId
                }
            });


        };
        return alertTransactionHistory;
    }]);

/* code modified by janarthanan */
angular.module('monitoringPortfolioApp.services').
    factory('alertService', ['$http', function ($http) {
        var alertApi = {};
        alertApi.FormPreview = function (option) {
            return $http({
                responseType: "arraybuffer",
                url: baseUrl() + "Api/MonitoringAlert/Preview"
                //params: {
                //    alertOption: option
                //}
            });
        };
        alertApi.GetAlertDetail = function (monitoringRecordId) {
            return $http({
                url: baseUrl() + 'Api/MonitoringAlert/GetTypeAlertDetail',
                params: {
                    messageId: monitoringRecordId
                }
            });
        }
        return alertApi;
    }]);
/* code ended here by janarthanan */

/*Monitoring Transactions Starts*/
angular.module("monitoringPortfolioApp.services").
    factory("monitoringHistoryService", ['$http', function ($http) {
        var monitoringTransactHistRecord = {};

        monitoringTransactHistRecord.getMonitoringTransactHistory = function (monitoringRecord) {
            var appUrl = baseUrl();

            return $http({
                url: appUrl + "Api/TransactionHistory/GetMonitoringTransactionHistory",
                params: {
                    debtor: angular.toJson(monitoringRecord)

                }
            });


        };
        return monitoringTransactHistRecord;
    }]);

/*Recent 60 days Alerts ends*/


angular.module("monitoringPortfolioApp.controllers", ['ngDialog']).
    controller("monitoringPortfolioController", ['$scope', '$uibModal', '$log', '$rootScope', '$filter', 'monitoringPortfolioService', 'monitoringHistoryService', 'alertHistoryService', 'alertService', 'busyWaitService', 'notificationService', 'ngDialog', function ($scope, $uibModal, $log, $rootScope, $filter, monitoringPortfolioService, monitoringHistoryService, alertHistoryService, alertService, busyWaitService, notificationService, ngDialog) {

        $scope.pieData = [];

        $scope.ResetPieChart = function () {
            while ($scope.pieData.length > 0) {
                $scope.pieData.pop();
            }
        }

        $scope.MonitoringRecordPieData = [
                { name: monitoringRecordsStatusActive, y: 0, yvalue: 0, color: '#6fb745', style: '{\'background-color\':\'#6fb745\'}' },
                { name: monitoringRecordsStatusSuspended, y: 0, yvalue: 0, color: '#1c94c4', style: '{\'background-color\':\'#1c94c4\'}' },
                { name: monitoringRecordsStatusUnresolved, y: 0, yvalue: 0, color: '#d32424', style: '{\'background-color\':\'#d32424\'}' },
                { name: monitoringRecordsStatusInactive, y: 0, yvalue: 0, color: '#d32424', style: '{\'background-color\':\'#d32424\'}' },
        ];

        $scope.RefreshPieChart = function () {
            setTimeout(function () {
                $scope.UpdatePieChart();
            }, 1);
        }

        $scope.UpdatePieChart = function () {
            var pieDate = [];
            pieDate.push($scope.AllRecordsCount);
            pieDate.push($scope.ActiveRecordsCount);
            pieDate.push($scope.SuspendedRecordsCount);
            pieDate.push($scope.UnresolvedRecordsCount);
            pieDate.push($scope.InactiveRecordsCount);

            $scope.UpdatePieChartValues(pieDate);


            if ($scope.AllRecordsCount < 1) {
                notificationService.flash({
                    type: toasterNotificationTypeInfo, title: "Search Results", body: "There are no records under this portfolio view."
                });
            }

        }

        $scope.UpdatePieChartValues = function (serverData) {

            $scope.ResetPieChart();
            for (var i = 1; i < serverData.length; i++) {
                if (serverData[0] > 0) {
                    $scope.MonitoringRecordPieData[i - 1].y = Math.ceil(((serverData[i] / serverData[0]) * 100));
                }
                else {
                    $scope.MonitoringRecordPieData[i - 1].y = 0;
                }
                $scope.MonitoringRecordPieData[i - 1].yvalue = serverData[i];

                if (serverData[i] > 0)
                    $scope.pieData.push($scope.MonitoringRecordPieData[i - 1]);
            }
            var element = angular.element($('#monitoringRecordPieChartDiv'));
            element.scope().$apply();
        }

        $scope.getPoints = function (piePoint) {
            piePoint.activeclass = "LiResultBorder";
            piePoint.inactivetabcolor = "LiResultBorder2";
            if (piePoint.name == monitoringRecordsStatusAll) $scope.getAllLoadedSearchRecords(piePoint);
            if (piePoint.name == monitoringRecordsStatusActive) $scope.getActiveRecords(piePoint);
            if (piePoint.name == monitoringRecordsStatusSuspended) $scope.getSuspendedRecords(piePoint);
            if (piePoint.name == monitoringRecordsStatusUnresolved) $scope.getUnresolvedRecords(piePoint);
        };

        $scope.LogMessage = function (message) {

            if (window.console && window.console.log) {
                window.console.log(message);
            }

        };

        $scope.monitoringPortfolioTabCompany = "";
        $scope.enableRecentAlertByDays = false;
        $scope.enableMonitoringGrid = true;
        $scope.enableCustomDate = false;
        $scope.errorDate = false;
        $scope.enablePortfolioSearchPanel = true;
        $scope.enableRecentAlertSearchPanel = false;
        $scope.IsYourRepresentativeEnabled = false;


        $scope.RedirectFilingPortfolioTab = function (tab) {
            var loc = iLienPortfolioRedirectUrl + "?filingPortfolioKey=" + tab.index;
            window.location = loc;


        };

        $scope.getIsUserAuthorized = function () {
            return $scope.isUserAuthorized;
        }

        $scope.IsUserActionEnabled = function () {
            if ($scope.CurrentGrid == INACTIVERECORDGRID)
                return false;
            return $scope.getIsUserAuthorized();
        }

        $scope.CloseYourRepWindow = function () {
        }



        $scope.showYourRepresentative = function (isVisible) {
            $scope.IsYourRepresentativeEnabled = isVisible;
        }

        $scope.closeRepresentative = function () {
            $scope.IsYourRepresentativeEnabled = false;
        }

        $scope.enableOrDisableRecentAlerts = function (booleanVal) {
            $scope.enableRecentAlertByDays = !!booleanVal;
            $scope.enableMonitoringGrid = !booleanVal;
        }

        $scope.parseDate = function (datetimeString) {
            if (datetimeString == undefined) {
                return null;
            }
            var date = datetimeString.split("/");
            return new Date(date[2], date[1] - 1, date[0]);
        };
        $scope.UpdateAdvancedSearchTimeFrameBy = function (value) {
            if ($scope.CurrentGrid != MONITORINGQGRID) {
                return;
            }
            if (value == 'Start Date') {
                $scope.AdvancedSearchByMonitoringRecordTimeFrameBy = $scope.MonitoringRecordTimeFrameByOptionsForFullMonitoringView;
            }
            else if (value == 'End Date') {
                $scope.AdvancedSearchByMonitoringRecordTimeFrameBy = $scope.MonitoringRecordTimeFrameByOptionsForFullMonitoringViewEndDate;
            }
            $scope.DateCombo = $scope.AdvancedSearchByMonitoringRecordTimeFrameBy[0];
        }
        $scope.ToggleMonitoringRecordType = function () {
            $scope.AdSearchByMonRecTypeSelection = this.AdSearchByMonRecTypeSelection;
        }
        $scope.ToggleMonitoringRecordstatus = function () {
            $scope.StatusCombo = this.StatusCombo;
        }
        $scope.Togglecalender = function (tr) {
            if (tr === "Custom") {
                $scope.enablePortFolioSearchCalendar = true;
                $scope.enableCustomDate = true;
                $scope.ValidateCustomDate(true);
            }
            else {
                $scope.errorDate = false;
                $scope.enableCustomDate = false;
                $scope.enablePortFolioSearchCalendar = false;
                $scope.StartDateError = false;
                $scope.EndDateError = false;
            }
        };

        $scope.IsMonitoringGridOpen = function () {
            return $scope.CurrentGrid == MONITORINGGRID || $scope.CurrentGrid == MONITORINGQGRID || $scope.CurrentGrid == INACTIVERECORDGRID;
        }
        $scope.IsMonitoringAdvSearchTypeRequried = function () {
            return $scope.CurrentGrid == MONITORINGQGRID || $scope.CurrentGrid == INACTIVERECORDGRID;
        }
        $scope.IsMonitoringAdvSearchStatusRequried = function () {
            return $scope.CurrentGrid == MONITORINGQGRID;
        }

        $scope.ValidateCustomDate = function (isWaitForUserInput) {

            if (isWaitForUserInput) {
                if ($scope.EndDate == undefined || $scope.StartDate == undefined) {

                    if ($scope.StartDate != undefined) {
                        $scope.StartDateError = false;
                    }
                    return;
                }
            }

            var IsFromFieldEmpty = true;
            var IsToFieldEmpty = true;

            var fromDateDiv = null;
            var endDateDiv = null;

            if ($scope.IsMonitoringGridOpen()) {
                fromDateDiv = "advancedSearchFromId";
                endDateDiv = "advancedSearchToId";
            }
            else if ($scope.CurrentGrid == ALERTGRID) {
                fromDateDiv = "advancedSearchAlertFromId";
                endDateDiv = "advancedSearchAlertToId";
            }

            var fromFieldData = document.getElementById(fromDateDiv).value;
            var toFieldData = document.getElementById(endDateDiv).value;

            IsFromFieldEmpty = fromFieldData == null || fromFieldData.length == 0;
            IsToFieldEmpty = toFieldData == null || toFieldData.length == 0;
            $scope.StartDateError = $scope.StartDate == undefined;
            $scope.EndDateError = $scope.EndDate == undefined;

            var startDateobj = new Date($scope.StartDate);
            var endDateobj = new Date($scope.EndDate);
            $scope.errorDate = endDateobj < startDateobj;

            var mandatoryMessage = null;
            var validationError = null;

            if ($scope.StartDateError && $scope.EndDateError) {
                mandatoryMessage = "Please enter From and To Date";
            }
            else if ($scope.StartDateError) {
                mandatoryMessage = "Please enter From Date";
            }
            else if ($scope.EndDateError) {
                mandatoryMessage = "Please enter To Date";
            }

            if ($scope.EndDateError && !IsToFieldEmpty)
                validationError = "Please enter Valid To Date";
            if ($scope.StartDateError && !IsFromFieldEmpty)
                validationError = "Please enter Valid From Date";
            if ($scope.EndDateError && !IsToFieldEmpty && $scope.StartDateError && !IsFromFieldEmpty) {
                validationError = "Please enter Valid From and To Date";
            }
            if (validationError != null) {
                notificationService.flash({
                    type: toasterNotificationTypeWarning, title: "Custom Date", body: validationError
                });
            }
            else if (mandatoryMessage != null) {
                notificationService.flash({
                    type: toasterNotificationTypeWarning, title: "Custom Date", body: mandatoryMessage
                });
            }

            if ($scope.errorDate) {
                notificationService.flash({
                    type: toasterNotificationTypeWarning, title: "Custom Date", body: "To Date must be after From Date"
                });
            }
            return;
        }

        $scope.$watch('StartDate + EndDate', function (newval, oldval) {
            if (newval === oldval) {
                return;
            }
            $scope.ValidateCustomDate(true);
        });


        $scope.quickViewSelectionOptions = [];

        $scope.quickViewSelectionOption = ["--Please Select--", quickViewFullMonitoringView, quickViewAlertsOnly, quickViewInActiveRecord];
        $scope.quickViewSelectionReadOnlyOption = ["--Please Select--", quickViewAlertsOnly];


        $scope.monitoringPortfolioSectionTabs = [];
        $scope.searchRecentAlertDatePreferences = ["Alert Date"];
        $scope.searchRecentAlertStatusPreferences = ["All", "Reviewed", "Not Reviewed"];
        $scope.AdSearchByMonRecType = ["All", "Business Entity", "UCC Debtor"];
        $scope.searchRecentAlertTypePreferences = ["All", "Tax Lien", "Original", "Continuation", "Termination", "Amendment", "Other", "Name Change", "Status Change"];


        $scope.MonitoringRecordStatusOptions = [monitoringRecordsStatusAll, monitoringRecordsStatusActive, monitoringRecordsStatusSuspended];
        $scope.MonitoringRecordStatusOptionsWithUnResolved = [monitoringRecordsStatusAll, monitoringRecordsStatusActive, monitoringRecordsStatusSuspended, monitoringRecordsStatusUnresolved];
        $scope.MonitoringRecordTimeFrameOptions = ["Start Date", "End Date"];

        $scope.MonitoringRecordTimeFrameByOptions = ["Last Week", "Today", "Last Month", "Last Quarter", "Last Year", "All", "Custom"];
        $scope.MonitoringRecordTimeFrameByOptionsForFullMonitoringViewEndDate = ["All", "Custom"];
        $scope.MonitoringRecordTimeFrameByOptionsForFullMonitoringView = ["Last Month", "Last Week", "Today", "Last Quarter", "Last Year", "All", "Custom"];


        $scope.AdvancedSearchByMonitoringRecordStatus = [];
        $scope.AdvancedSearchByMonitoringRecordTimeFrame = [];
        $scope.AdvancedSearchByMonitoringRecordTimeFrameBy = [];

        $scope.searchActionPreferences = ["Search", "UCC3"];
        $scope.IsUnResolvedTabVisible = false;

        $scope.monitoringPortfolioSectionTabs = [
        {
            index: 0,
            title: 'Filing Portfolio',
            content: '../../Views/partials/tab1.cshtml', tabId: "tab1",
            tabclass: 'tab1', active: false, disabled: false, tabevent: $scope.RedirectFilingPortfolioTab
        },
        {
            index: 1, title: 'Search/Order Portfolio', ccontent: '../../Views/partials/tab1.cshtml', tabId: "tab2", tabclass: 'tab2', active: false, disabled: false, tabevent: $scope.RedirectFilingPortfolioTab
        },
        {
            index: 2, title: 'Combined Portfolio', content: '../../Views/partials/tab1.cshtml', tabId: "tab3", tabclass: 'tab3', active: false, disabled: false, tabevent: $scope.RedirectFilingPortfolioTab
        },
        {
            index: 3, title: 'Monitoring Portfolio', content: '../../Views/partials/tab1.cshtml', tabId: "tab4", tabclass: 'tab4', active: true, disabled: false, tabevent: $scope.RedirectFilingPortfolioTab
        }
        ];

        $scope.updateCurrentTab = function (tab) {
            $scope.currentTabColor = tab.tabclass;
        };

        $scope.monitoringGridPages = ["10", "20", "30", "50"];
        $scope.recentAlertsPageSize = ["10", "20", "30", "50"];

        $scope.MinTypeAheadCharLength = 3;
        $scope.monitoringRecordsList = null;
        $scope.TotalMonitoringRecords = 0;

        $scope.TotalAlertsInGrid = 0;
        $scope.MonitoringRecordsGridCurrentPage = 1;
        $scope.MonitoringRecordsGridMaxPage = 0;
        $scope.MonitoringRecordsGridPageSize = 0;
        $scope.MonitoringRecordsGridSortKey = 0;
        $scope.IsMonitoringRecordsGridSortAsc = true;
        $scope.MonitoringRecordsSearchCriteria = null;
        $scope.currentTabColor = 'tab4';
        $scope.searkeyError = false;
        $scope.enablePortFolioSearchCalendar = false;
        $scope.HeaderActiveTab = "All";
        $scope.filterByTabStatus = null;
        $scope.HeaderActiveTabByClicking = '';
        $scope.portfolioTabsStatusArr = [];
        $scope.highlightedRow = '';
        $scope.unresolvedHighlightedRowsArr = [];
        $scope.CurrentGrid = MONITORINGQGRID;
        $scope.IsMonitoringGridTabAll = true;
        $scope.IsMonitoringGridTabActive = true;
        $scope.IsMonitoringGridTabSuspended = true;
        $scope.IsMonitoringGridTabUnResolved = true;
        $scope.isCssLoadingWindow = false;
        $scope.unresolvedCurrentRowItem = null;

        $scope.IsAlertIncludedInExport = true;
        $scope.IsOnlyRecentAlertsRequired = true;

        $scope.AllRecordsCount;
        $scope.ActiveRecordsCount;
        $scope.SuspendedRecordsCount;
        $scope.UnresolvedRecordsCount;
        $scope.InactiveRecordsCount;
        $scope.isAlertMailed = false;



        /*************************** date picker ****************************************/
        $scope.openStartDate = function () {
            $scope.status.openedStartDate = true;
        };
        $scope.openEndDate = function () {
            $scope.status.openedEndDate = true;
        };

        $scope.dateOptions = {
            formatYear: 'yyyy',
            startingDay: 1,
            showWeeks: false
        };

        $scope.format = 'MM/dd/yyyy';

        $scope.status = {
            openedStartDate: false,
            openedEndDate: false
        };


        /*************************** date picker ****************************************/
        $scope.isToasterExists = function () {
            var toasterExists = angular.element(document).find('body').find('.toast')[0];
            if (!toasterExists) { return true } else {
                return false;
            }
        }

        $scope.SearchMonitoringRecords = function () {

            $scope.StartDateError = false;
            $scope.EndDateError = false;
            $scope.searkeyError = false;

            var searchKeyLength = $("#search-key").val().length;
            if (searchKeyLength <= 2 && searchKeyLength > 0) {
                $("#search-key").removeClass("ng-pristine").removeClass("ng-valid").removeClass("ng-valid-minlength");
                $("#search-key").addClass("ng-invalid").addClass("ng-valid-required").addClass("ng-invalid-minlength");
                $scope.searkeyError = true;

                if ($scope.isToasterExists()) {
                    notificationService.flash({
                        type: toasterNotificationTypeWarning,
                        title: "Search Key Validation",
                        body: "Please enter atleast 3 characters"
                    });
                }


            }
            if ($scope.enableCustomDate) {
                $scope.ValidateCustomDate(false);
            }
            if (!$scope.StartDateError && !$scope.EndDateError && !$scope.errorDate && !$scope.searkeyError) {
                $scope.searchRecords(true, false);
            }

        };

        $scope.prevent = function () {
            if (!(event.keyCode > 47 && event.keyCode < 58) && !(event.keyCode > 64 && event.keyCode < 91) &&
                !(event.keyCode > 96 && event.keyCode < 123)) { // Typing Back Key
                return;
            } else {
                if ($("#search-key").val().length >= 3) {
                } else {
                    if ($("#search-key").val().length == 0) {
                    }
                }

                $scope.searkeyError = false;
            }
        };

        $scope.initApplicationBody = function () {
            $scope.quickViewSelectionOptions = $scope.quickViewSelectionOption;
            setTimeout(function () {
                $scope.BuildCustomMenu();
            }, 1);
            $scope.BuildPortfolio();
        };

        $scope.init = function () {
            $scope.clearSearchOptions();
            $scope.ResetMonitoringRecordsSort();
            $scope.PerformTabEnableByparams(true, true, true, true);
            $scope.initApplicationBody();

        };
        $scope.BuildPortfolio = function () {
            var AuthenticatedUserStatus = monitoringPortfolioService.getAuthenticatedUserStatus();
            AuthenticatedUserStatus.then(function (response) {
                $scope.isUserAuthorized = !response.data; // true => Active customer, false => Non Active customer
                if (!$scope.getIsUserAuthorized()) { // Enable Recent Alerts only for ReadOnly User
                    $scope.buildQuickView(quickViewAlertsOnly, true);
                }
                else {
                    $scope.buildQuickView(quickViewFullMonitoringView, true);
                }
            });
        };
        $scope.BuildCustomMenu = function () {
           monitoringPortfolioService.GetCustomMenu().then(function (response) {
                var companyNameIndex = 0;
                var customMenuIndex = 2;
                var moreMenuIndex = 3;
                var filingMenuIndex = 6;

                if (response.data.length > customMenuIndex) {
                    var element = document.getElementById('customLinksDiv');
                    element.innerHTML = response.data[customMenuIndex];
                }
               
                if (response.data.length > moreMenuIndex) {
                    element = document.getElementById('moreLinksDiv');
                    element.innerHTML = response.data[moreMenuIndex];
                }

                if (response.data.length > companyNameIndex) {
                    var company = response.data[companyNameIndex];
                    if (company.length > 0) {
                        company += ' > ';
                    }
                    var portfolioTab = $scope.monitoringPortfolioSectionTabs[$scope.monitoringPortfolioSectionTabs.length - 1];
                    portfolioTab.title = company + portfolioTab.title;
                    portfolioTab.active = true;
                }
                if (response.data.length > filingMenuIndex) {
                    var SearchEnable = response.data[filingMenuIndex - 2];
                    var OrdersEnable = response.data[filingMenuIndex - 1];
                    var FilingsEnable = response.data[filingMenuIndex];
                    $scope.tabMenuDisable(SearchEnable, OrdersEnable, FilingsEnable);
                }

            });
        };
        /* code added begin by jana */

        $scope.tabMenuDisable = function (searchEnable, orderEnable, filingEnable) {
            if (filingEnable == "Y" && (searchEnable != "Y" && orderEnable != "Y")) {

                angular.forEach($scope.monitoringPortfolioSectionTabs, function (tabObj, $index) {
                    tabObj.disabled = true;
                    if (tabObj.index == 1 || tabObj.index == 2) {
                        tabObj.disabled = false;
                    }
                });
            }

            if ((searchEnable == "Y" || orderEnable == "Y") && filingEnable == "N") {
                angular.forEach($scope.monitoringPortfolioSectionTabs, function (tabObj, $index) {
                    tabObj.disabled = true;
                    if (tabObj.index == 0 || tabObj.index == 2) {
                        tabObj.disabled = false;
                    }

                });
            }

        };
        /* code added ended by jana */
        $scope.MonitoringRecordsGridPaginate = function (delta) {
            var previousPage = $scope.MonitoringRecordsGridCurrentPage;
            $scope.MonitoringRecordsGridCurrentPage += delta;
            if ($scope.MonitoringRecordsGridCurrentPage < 1 || $scope.MonitoringRecordsGridCurrentPage > $scope.MonitoringRecordsGridMaxPage) {
                $scope.MonitoringRecordsGridCurrentPage = previousPage;
            } else
                $scope.searchRecords(false, false);
        };
        $scope.GetMaxAlertsPerMonitoringRecord = function () {
            return maxAlertsPerMonitoringRecord;
        };

        $scope.gotoPage = function (newPageNumber) {

            var navigationLevel = newPageNumber - $scope.MonitoringRecordsGridCurrentPage;
            $scope.MonitoringRecordsGridPaginate(navigationLevel);

        }
        $scope.PopulateGridPages = function (num) {
            var array = [];
            for (var x = 1; x <= num; x++) {
                array[x] = x;
            }
            return array;
        };
        $scope.ResetMonitoringRecordsSort = function () {
            $scope.MonitoringRecordsGridPageSize = 50;
            $scope.MonitoringRecordsGridSortKey = "DebtorName";
            $scope.IsMonitoringRecordsGridSortAsc = true;
            $scope.MonitoringRecordsGridCurrentPage = 1;
        };
        $scope.sortMonitoringRecords = function (keyname) {

            if ($scope.monitoringRecordsList.length < 2) return; // Enable sorting only when there are atleast 2 records in the grid

            if ($scope.MonitoringRecordsGridSortKey !== keyname) {
                $scope.IsMonitoringRecordsGridSortAsc = true;
            } else {
                $scope.IsMonitoringRecordsGridSortAsc = !$scope.IsMonitoringRecordsGridSortAsc;
            }
            $scope.MonitoringRecordsGridSortKey = keyname;
            $scope.MonitoringRecordsGridPaginate(0);
        }; //Toggle the Expansion icon by adding/removing the class on the Onclick Event
        $scope.expandMonitoringRecord = function (record) {
            $scope.isDebtorExpanded = false;
            $scope.holdDebtorExpandedData;
            var targetToBeExpanded = "#alertSubGrid" + record.CustomDivId;
            var isExpanded = record.Expanded;
            record.Expanded = !record.Expanded;
            if (isExpanded) {
                $scope.isDebtorExpanded = false;
                $(targetToBeExpanded).collapse("toggle");
            } else {
                $scope.ExpandMonitoringAlerts(record, targetToBeExpanded);
            }
        };
        $scope.DeleteUnResolvedRecord = function (record) {          

            var retVal = confirm("Are you sure you want to remove this record?");           
          
            if (retVal == true) {
                var busywindow = busyWaitService.Start();
                monitoringPortfolioService.DeleteUnResolvedRecord(record)
                    .then(function (response) {
                        if (response.data === true) {
                            ResetItemstoBeResolved();
                            busywindow.close();
                            setTimeout(function () {
                                $scope.searchRecords(false, true);
                                notificationService.flash({ type: toasterNotificationTypeSuccess, title: "", body: "Record has been removed." });
                            }, 100);
                        }
                    })
                .catch(function (response) {
                    busywindow.close();
                });
            }
        };


        $scope.UpdateMonitoringRecordStatus = function (record, newStatusCode, newStatus) {
            $scope.UpdateCache();
            var busywindow = busyWaitService.Start();
            var debtorChangeStatus = {
                'RecordIdentifier': record.RecordIdentifier,
                'MonitoringType': record.MonitoringTypeAbbr,
                'StateCode': record.StateCode,
                'DebtorName': record.DebtorName,
                'OldStatus': record.MonitoringStatusDesc,
                'City': record.City,
                'NewStatus': newStatus,
            };
            monitoringPortfolioService.UpdateMonitoringRecordStatus(debtorChangeStatus).then(function (response) {

                if (response.data === true) {
                    record.Status = newStatusCode;
                    record.MonitoringStatusDesc = newStatus;
                    busywindow.close();

                    setTimeout(function () {
                        //   $scope.RefreshTabHeader(newStatusCode);
                        $scope.searchRecords(false, true);
                        notificationService.flash({
                            type: toasterNotificationTypeSuccess, title: "Status Update", body: "Your changes have been saved."
                        });

                    }, 100);
                } else {
                    notificationService.flash({
                        type: toasterNotificationTypeError, title: "Status Update", body: "Your changes have not been saved."
                    });
                }

            })
    .catch(function (response) {
        busywindow.close();
    });

        };

        // scope level
        $scope.UpdateMonitoringAlertStatus = function (record, statusType, newStatus, monitoringRecordAlerts) {

            var busywindow = busyWaitService.Start();
            var alertChangeStatus = {
                'MessageId': record,
                'AlertStatus': newStatus,
                'ChangeType': statusType
            };
            monitoringPortfolioService.UpdateMonitoringAlertStatus(alertChangeStatus).then(function (response) {

                if (response.data === true) {

                    monitoringRecordAlerts.Reviewed = newStatus;

                    notificationService.flash({
                        type: toasterNotificationTypeSuccess, title: "Status Update", body: "Your changes have been saved."
                    });


                } else {
                    notificationService.flash({
                        type: toasterNotificationTypeError, title: "Status Update", body: "Your changes have not been saved."
                    });
                }
                busywindow.close();
            })
                .catch(function (response) {
                    busywindow.close();
                });

        };

        $scope.ToggleAdvancedSearch = function (id, visible) {
            if (visible)
                $(id).collapse({ 'toggle': false }).collapse("show");
            else
                $(id).collapse({ 'toggle': false }).collapse("hide");
        }

        $scope.Portfolioexpandstatus = false;
        $scope.Alertexpandstatus = false;

        $scope.PortfolioExpandSearch = function () {
            $scope.Portfolioexpandstatus = true;
            $scope.ToggleAdvancedSearch("#portfoliocollapsetarget", $scope.Portfolioexpandstatus);
        };
        $scope.AlertExpandSearch = function () {
            $scope.Alertexpandstatus = true;
            $scope.ToggleAdvancedSearch("#alertcollapsetarget", $scope.Alertexpandstatus);
        };

        $scope.PortfolioCollapseSearch = function () {
            $scope.Portfolioexpandstatus = false;
            $scope.ToggleAdvancedSearch("#portfoliocollapsetarget", $scope.Portfolioexpandstatus);
        };
        $scope.AlertCollapseSearch = function () {
            $scope.Alertexpandstatus = false;
            $scope.ToggleAdvancedSearch("#alertcollapsetarget", $scope.Alertexpandstatus);
        };

        $scope.clearSearch = function () {
            $scope.clearSearchInput();
            $("#search-key").addClass("ng-pristine");
        };

        $scope.ExpandMonitoringAlerts = function (record, targetToBeExpanded) {
            record.Alerts = {
            };
            var busywindow = busyWaitService.Start();
            var debtor = {
                'RecordIdentifier': record.RecordIdentifier,
                'MonitoringType': record.MonitoringTypeAbbr,
                'StateCode': record.StateCode,
                'DebtorName': record.DebtorName
            };

            monitoringPortfolioService.getAlerts(debtor).then(function (response) {
                busywindow.close();
                var alertDetails = response.data;
                var alerts = alertDetails._monitoringDebtorAlertDetails;
                var filings = alertDetails._monitoringAlertActiveFilings;



                if (alerts != null && alerts.length > maxAlertsPerMonitoringRecord) {
                    record.Alerts = alerts.slice(0, maxAlertsPerMonitoringRecord);
                } else {
                    record.Alerts = alerts;
                }
                record.Filings = filings;

                $scope.isDebtorExpanded = true;
                $scope.holdDebtorExpandedData = alerts;
                $(targetToBeExpanded).collapse("toggle");

            })
                .catch(function (response) {
                    busywindow.close();
                });

        };

        $scope.RefreshTabHeader = function (newStatusCode) {
            var IsAdvancedSearchedStatusbyAll = $scope.StatusCombo == monitoringRecordsStatusAll;
            var IsTabSelectedByAll = $scope.HeaderActiveTab == monitoringRecordsStatusAll;

            if (IsAdvancedSearchedStatusbyAll) {
                if (newStatusCode == 2) //Suspended
                {
                    if ($scope.SuspendedRecordsCount > 0)
                        $scope.SuspendedRecordsCount++;

                    if (IsTabSelectedByAll) {
                        $scope.ActiveRecordsCount--;
                    }

                }
                if (newStatusCode == 1) //Active
                {
                    if ($scope.ActiveRecordsCount > 0)
                        $scope.ActiveRecordsCount++;
                    if (IsTabSelectedByAll) {
                        $scope.SuspendedRecordsCount--;
                    }
                }
            }
        }

        $scope.PerformTabEnableByparams = function (hasAll, hasActive, hasSuspended, hasUnResolved) {
            if (!hasAll && !hasActive && !hasSuspended && !hasUnResolved) {
                $scope.portfolioTabsStatusArr = [];
            }
            else {
                $scope.portfolioTabsStatusArr = [{
                    "name": monitoringRecordsStatusAll, "show": hasAll
                }, {
                    "name": monitoringRecordsStatusActive, "show": hasActive
                },
                {
                    "name": monitoringRecordsStatusSuspended, "show": hasSuspended
                }, {
                    "name": monitoringRecordsStatusUnresolved, "show": hasUnResolved
                }];
            }

        }

        $scope.PerformTabHighLight = function (status) {
            $scope.HeaderActiveTab = status;
        }
        $scope.PerformTabNavigation = function (status) {
            $scope.filterByTabStatus = status;
        }

        $scope.GetTabbedResult = function (status, skipEmptyCheck) {
            $scope.MonitoringRecordsGridCurrentPage = 1;
            if ($scope.TotalMonitoringRecords > 0 || skipEmptyCheck) {
                $scope.PerformTabHighLight(status);
                $scope.PerformTabNavigation(status);
                $scope.searchRecords(false, false);
            }
        }



        $scope.handlePortfiloTabsByClicking = function (tab) {
            $scope.PerformTabHighLight(tab.name);
            $scope.HeaderActiveTabByClicking = tab.name;
        }

        $scope.getAllLoadedSearchRecords = function (tab) {
            if (!tab.hasOwnProperty("color")) $scope.setPoints(tab.name);
            $scope.GetTabbedResult(tab.name, true);
            $scope.IsUnResolvedTabVisible = false;
            $scope.handlePortfiloTabsByClicking(tab);
        }
        $scope.getActiveRecords = function (tab) {
            if (!tab.hasOwnProperty("color")) $scope.setPoints(tab.name);
            $scope.GetTabbedResult(tab.name, true);
            $scope.IsUnResolvedTabVisible = false;
            $scope.handlePortfiloTabsByClicking(tab);

        }
        $scope.getSuspendedRecords = function (tab) {
            if (!tab.hasOwnProperty("color")) $scope.setPoints(tab.name);
            $scope.GetTabbedResult(tab.name, true);
            $scope.IsUnResolvedTabVisible = false;
            $scope.handlePortfiloTabsByClicking(tab);
        }

        $scope.getUnresolvedRecords = function (tab) {
            if (!tab.hasOwnProperty("color")) $scope.setPoints(tab.name);
            $scope.GetTabbedResult(tab.name, true);
            $scope.IsUnResolvedTabVisible = true;
            $scope.handlePortfiloTabsByClicking(tab);
        }

        $scope.highLightPortfolioTabs = function (isRefreshGridBySearch) {
            if ($scope.HeaderActiveTabByClicking != '' && $scope.HeaderActiveTabByClicking != undefined) { // user clicks on Tabs                
                $scope.PerformTabHighLight($scope.HeaderActiveTabByClicking);
                $scope.HeaderActiveTabByClicking = '';
            }
            var activeHeader = isRefreshGridBySearch ? $scope.StatusCombo : $scope.HeaderActiveTab;
            $scope.PerformTabHighLight(activeHeader);
            $scope.PerformTabNavigation(activeHeader);
        }

        $scope.isActiveTab = function (tab) {
            return $scope.HeaderActiveTab == tab.name;
        }

        $scope.IsPortfolioTabBusy = function () {
            return $scope.portfolioTabsStatusArr.length == 0;
        }

        $scope.IsPortfolioTabVisible = function (tab) {
            if ($scope.portfolioTabsStatusArr.length > 0) {
                var newobj = $filter("filter")($scope.portfolioTabsStatusArr, {
                    name: tab.name
                })[0];
                return newobj.show;
            }
            else {
                return false;
            }
        }
        $scope.ResolveDate = function (originalDate) {
            if (originalDate == undefined) return originalDate;
            var newDate = new Date(originalDate);
            var year = newDate.getFullYear();
            var month = newDate.getMonth() + 1;
            var day = newDate.getDate();
            var resolvedDate = month + '/' + day + '/' + year; //'MM/dd/yyyy';
            return resolvedDate;
        }

        $scope.ResetTabStatictics = function () {
            $scope.AllRecordsCount = $scope.ActiveRecordsCount = $scope.SuspendedRecordsCount = $scope.UnresolvedRecordsCount = 0;
        }

        $scope.updateTabStaticticsOnReset = function (result) {
            $scope.ResetTabStatictics();
            var activeHeader = $scope.StatusCombo;
            var isAllSelected = activeHeader == monitoringRecordsStatusAll;
            var isActiveSelected = activeHeader == monitoringRecordsStatusActive;
            var isSuspendedSelected = activeHeader == monitoringRecordsStatusSuspended;
            var isUnResolvedSelected = activeHeader == monitoringRecordsStatusUnresolved;

            if (isAllSelected)
                $scope.AllRecordsCount = result.AllRecordsCount;
            if (isActiveSelected || isAllSelected)
                $scope.ActiveRecordsCount = result.ActiveRecordsCount;
            if (isSuspendedSelected || isAllSelected)
                $scope.SuspendedRecordsCount = result.SuspendedRecordsCount;
            if (isUnResolvedSelected || isAllSelected)
                $scope.UnresolvedRecordsCount = result.UnresolvedRecordsCount;
            if ($scope.AllRecordsCount < 1) {
                $scope.AllRecordsCount = $scope.ActiveRecordsCount + $scope.SuspendedRecordsCount + $scope.UnresolvedRecordsCount;
            }
            $scope.InactiveRecordsCount = result.InactiveRecordsCount;
            if ($scope.InactiveRecordsCount > 0) {
                $scope.AllRecordsCount = $scope.InactiveRecordsCount;
            }
            $scope.IsMonitoringGridTabAll = $scope.AllRecordsCount > 0 && isAllSelected;
            $scope.IsMonitoringGridTabActive = $scope.ActiveRecordsCount > 0 || isActiveSelected;
            $scope.IsMonitoringGridTabSuspended = $scope.SuspendedRecordsCount > 0 || isSuspendedSelected;
            $scope.IsMonitoringGridTabUnResolved = $scope.UnresolvedRecordsCount > 0 || isUnResolvedSelected;

            $scope.PerformTabEnableByparams($scope.IsMonitoringGridTabAll, $scope.IsMonitoringGridTabActive, $scope.IsMonitoringGridTabSuspended, $scope.IsMonitoringGridTabUnResolved);

        }

        $scope.updateTabStaticticsOnUnResolve = function (result) {

            var isAdvancedSearchByAll = $scope.StatusCombo == monitoringRecordsStatusAll;
            var isAdvancedUnResolvedSelected = $scope.StatusCombo == monitoringRecordsStatusUnresolved;

            if (isAdvancedUnResolvedSelected) {
                $scope.UnresolvedRecordsCount = result.UnresolvedRecordsCount;
                $scope.AllRecordsCount = $scope.UnresolvedRecordsCount;
                $scope.PerformTabEnableByparams(false, false, false, true);
            }
            if (isAdvancedSearchByAll) {
                $scope.UnresolvedRecordsCount = result.UnresolvedRecordsCount;
                $scope.ActiveRecordsCount = result.ActiveRecordsCount;
                $scope.SuspendedRecordsCount = result.SuspendedRecordsCount;
                $scope.AllRecordsCount = $scope.UnresolvedRecordsCount + $scope.SuspendedRecordsCount + $scope.UnresolvedRecordsCount;

                $scope.IsMonitoringGridTabAll = $scope.AllRecordsCount > 0;
                $scope.IsMonitoringGridTabActive = $scope.ActiveRecordsCount > 0;
                $scope.IsMonitoringGridTabSuspended = $scope.SuspendedRecordsCount > 0;
                $scope.IsMonitoringGridTabUnResolved = $scope.UnresolvedRecordsCount > 0;

                $scope.PerformTabEnableByparams($scope.IsMonitoringGridTabAll, $scope.IsMonitoringGridTabActive, $scope.IsMonitoringGridTabSuspended, $scope.IsMonitoringGridTabUnResolved);

            }

        }

        $scope.updateTabStaticticsOnRefresh = function (result, isRefresh) {

            var isAdvancedSearchByAll = $scope.StatusCombo == monitoringRecordsStatusAll;

            var activeHeader = $scope.HeaderActiveTab;
            var isAllSelected = activeHeader == monitoringRecordsStatusAll;
            var isActiveSelected = activeHeader == monitoringRecordsStatusActive;
            var isSuspendedSelected = activeHeader == monitoringRecordsStatusSuspended;
            var isUnResolvedSelected = activeHeader == monitoringRecordsStatusUnresolved;

            if (isAllSelected) {
                $scope.AllRecordsCount = result.AllRecordsCount;
                $scope.SuspendedRecordsCount = result.SuspendedRecordsCount;
                $scope.UnresolvedRecordsCount = result.UnresolvedRecordsCount;
                $scope.ActiveRecordsCount = result.ActiveRecordsCount;
            }
            if (isActiveSelected) {

                if (isRefresh) {
                    if (isAdvancedSearchByAll) {
                        $scope.SuspendedRecordsCount++;
                    }
                }
                $scope.ActiveRecordsCount = result.ActiveRecordsCount;
            }
            if (isSuspendedSelected) {

                if (isRefresh) {
                    if (isAdvancedSearchByAll) {
                        $scope.ActiveRecordsCount++;
                    }
                }
                $scope.SuspendedRecordsCount = result.SuspendedRecordsCount;
            }
            if (isUnResolvedSelected) {
                $scope.UnresolvedRecordsCount = result.UnresolvedRecordsCount;
            }
            if ($scope.AllRecordsCount < 1) {
                $scope.AllRecordsCount = $scope.ActiveRecordsCount + $scope.SuspendedRecordsCount + $scope.UnresolvedRecordsCount;
            }
            $scope.InactiveRecordsCount = result.InactiveRecordsCount;
            if ($scope.InactiveRecordsCount > 0) {
                $scope.AllRecordsCount = $scope.InactiveRecordsCount;
            }

            $scope.IsMonitoringGridTabAll = isAdvancedSearchByAll && $scope.AllRecordsCount > 0;
            $scope.IsMonitoringGridTabActive = $scope.ActiveRecordsCount > 0 || isActiveSelected;
            $scope.IsMonitoringGridTabSuspended = $scope.SuspendedRecordsCount > 0 || isSuspendedSelected;
            $scope.IsMonitoringGridTabUnResolved = $scope.UnresolvedRecordsCount > 0 || isUnResolvedSelected;

            $scope.PerformTabEnableByparams($scope.IsMonitoringGridTabAll, $scope.IsMonitoringGridTabActive, $scope.IsMonitoringGridTabSuspended, $scope.IsMonitoringGridTabUnResolved);

        }


        $scope.updateTabStatictics = function (response, isResetPagination, isRefresh) {
            if (isResetPagination) {
                $scope.updateTabStaticticsOnReset(response.data);
            }
            else {
                $scope.updateTabStaticticsOnRefresh(response.data, isRefresh);
            }
            $scope.highLightPortfolioTabs(isResetPagination);
        }

        $scope.showFlashMessage = function (notificationType, titleText, bodyText) {
            notificationService.flash({
                type: notificationType, title: titleText, body: bodyText
            });
        }

        $scope.GetFileTimeStamp = function (now) {
            var dateParam = "_" + (now.getMonth() + 1) + "_" + now.getDate() + "_" + now.getFullYear();
            dateParam += "_" + now.getHours() + ":" + now.getMinutes() + ":" + now.getSeconds();
            return dateParam;
        }
        $scope.OpenFormPreview = function (response, filename) {

            var fileName = filename + $scope.GetFileTimeStamp(new Date()) + '.xlsx';
            var contentType = 'application/octet-stream';
            try {
                var blob = new Blob([response.data], { type: contentType });
                if (navigator.msSaveBlob)
                    navigator.msSaveOrOpenBlob(blob, fileName);
                else //chrome
                {
                    window.requestFileSystem = window.requestFileSystem || window.webkitRequestFileSystem;
                    window.requestFileSystem(window.TEMPORARY, 1024 * 1024, function (fs) {
                        fs.root.getFile(fileName, {
                            create: true
                        }, function (fileEntry) {
                            fileEntry.createWriter(function (fileWriter) {
                                var blob = new Blob([response.data], { type: contentType });
                                fileWriter.addEventListener("writeend", function () {
                                    var fileUrl = fileEntry.toURL();
                                    var link = document.createElement('a');
                                    link.href = fileUrl;
                                    link.download = fileName;
                                    document.body.appendChild(link);
                                    link.click();
                                    document.body.removeChild(link);
                                }, false);
                                fileWriter.write(blob);
                            }, function () { });
                        }, function () { });
                    }, function () { });
                }
            } catch (ex) {

            }
        }
        $scope.MonitoringRecordPreview = function (IsAlertIncludedInExport, IsOnlyRecentAlertsRequired) {

            var count = 0;
            if (IsAlertIncludedInExport) {
                if (IsOnlyRecentAlertsRequired) {
                    count = maxAlertsPerMonitoringRecord;
                }
                else {
                    count = -1;
                }
            }

            var busywindow = busyWaitService.Start();
            monitoringPortfolioService.FormPreview(count, $scope.MonitoringRecordsSearchCriteria)
                      .then(function (response) {
                          busywindow.close();
                          $scope.OpenFormPreview(response, 'MonitoringRecords');
                      })
                .catch(function (response) {
                    busywindow.close();
                });
        }
        $scope.AlertRecordPreview = function (count) {
            var busywindow = busyWaitService.Start();
            alertService.FormPreview(count)
                      .then(function (response) {
                          busywindow.close();
                          $scope.OpenFormPreview(response, "MonitoringAlerts");
                      })
                      .catch(function (response) {
                          busywindow.close();
                      });
        }

        $scope.TriggerMonitoringAlertOptins = function () {
            ngDialog.open({
                template: 'confirmAlertCount', scope: $scope
            });
        }



        $scope.TriggerFormPreview = function () {

            if ($scope.IsMonitoringGridOpen()) {

                if ($scope.CurrentGrid == INACTIVERECORDGRID) {
                    return; // Export option for Inactive Records is not required.
                }

                if ($scope.TotalMonitoringRecords == 0) {
                    notificationService.flash({
                        type: toasterNotificationTypeInfo, title: "Form Preview", body: "No data to export"
                    });
                    return;
                }

                if ($scope.IsUnResolvedTabVisible == true) {
                    $scope.MonitoringRecordPreview(false, false);
                    return;
                }

                $scope.IsAlertIncludedInExport = true;
                $scope.IsOnlyRecentAlertsRequired = true;
                ngDialog.open({ template: 'confirmIfAlertsRequired', scope: $scope });
            }
            if ($scope.CurrentGrid == ALERTGRID) {
                if ($scope.TotalAlertsInGrid == 0) {
                    notificationService.flash({
                        type: toasterNotificationTypeInfo, title: "Form Preview", body: "No data to export"
                    });
                    return;
                }
                $scope.AlertRecordPreview(0);
            }

        }
        $scope.searchRecords = function (isResetPagination, isGridRefreshRequired) {
            if (!isGridRefreshRequired) {
                $scope.monitoringRecordsList = null;
            }
            setTimeout(function () {
                $scope.searchMonitoringRecords(isResetPagination, isGridRefreshRequired);
            }, 5);

        };
        $scope.searchMonitoringRecords = function (isResetPagination, isGridRefreshRequired) {
            if (isResetPagination === true) {
                $scope.IsUnResolvedTabVisible = false;
                if ($scope.StatusCombo == monitoringRecordsStatusUnresolved) {
                    $scope.IsUnResolvedTabVisible = true;
                }
                $scope.ResetMonitoringRecordsSort();
                $scope.MonitoringRecordsSearchCriteria = {
                    'SearchKey': $scope.EntitySearchId,
                    'TimeFrameBy': $scope.TimeframeCombo.replace(/\s+/g, ""),
                    'TimeFrame': $scope.DateCombo.replace(/\s+/g, ""),
                    'Status': $scope.StatusCombo,
                    'PageNumber': $scope.MonitoringRecordsGridCurrentPage,
                    'PageSize': $scope.MonitoringRecordsGridPageSize,
                    'SortColumn': $scope.MonitoringRecordsGridSortKey,
                    'IsAscending': $scope.IsMonitoringRecordsGridSortAsc,
                    'TimeframeFromStr': $scope.ResolveDate($scope.StartDate),
                    'TimeframeToStr': $scope.ResolveDate($scope.EndDate),
                    'RecentAlertBy': $scope.RecentAlertCombo,
                    'FilterStatus': null,
                    'Type': $scope.AdSearchByMonRecTypeSelection
                };
            } else {
                if ($scope.MonitoringRecordsSearchCriteria == null) return;
                $scope.MonitoringRecordsSearchCriteria.PageNumber = $scope.MonitoringRecordsGridCurrentPage;
                $scope.MonitoringRecordsSearchCriteria.PageSize = $scope.MonitoringRecordsGridPageSize;
                $scope.MonitoringRecordsSearchCriteria.SortColumn = $scope.MonitoringRecordsGridSortKey,
                $scope.MonitoringRecordsSearchCriteria.IsAscending = $scope.IsMonitoringRecordsGridSortAsc;
                $scope.MonitoringRecordsSearchCriteria.FilterStatus = $scope.filterByTabStatus;
            }

            if ($scope.CurrentGrid == INACTIVERECORDGRID) {
                $scope.MonitoringRecordsSearchCriteria.Status = monitoringRecordsStatusInactive;
                $scope.MonitoringRecordsSearchCriteria.FilterStatus = monitoringRecordsStatusInactive;
            }
            $scope.MonitoringRecordsSearchCriteria.IsReadStats = false;
            var busywindow = busyWaitService.Start();
            $scope.enableOrDisableRecentAlerts(false);



            function filterMasterData(response, isRefresh) {
                var result = response.data;
                $scope.TotalMonitoringRecords = result.Count;
                $scope.monitoringRecordsList = result.Records;
                $scope.updateTabStatictics(response, isResetPagination, isRefresh);
                $scope.RefreshPieChart();
            }




            function paginationRecords() {
                $scope.MonitoringRecordsGridMaxPage = Math.ceil($scope.TotalMonitoringRecords / $scope.MonitoringRecordsGridPageSize);

                angular.forEach($scope.monitoringRecordsList, function (record, $index) {
                    record.Expanded = false;
                    record.CustomDivId = $index;
                });
            }

            $scope.MonitoringRecordsSearchCriteria = $scope.GetCache($scope.MonitoringRecordsSearchCriteria);
            monitoringPortfolioService.searchRecords($scope.MonitoringRecordsSearchCriteria)
                .then(function (response) {
                    filterMasterData(response, isGridRefreshRequired);
                    paginationRecords();
                    busywindow.close();
                })
                .catch(function (response) {
                    busywindow.close();
                    $scope.monitoringRecordsList = null;
                });

        };

        $scope.UpdateCache = function () {
            $scope.ClientCache = new Date().getTime() + '' + getLoggedInUserId();
        }

        $scope.GetCache = function (obj) {
            if ($scope.ClientCache == null)
                $scope.UpdateCache();
            obj.ClientCache = $scope.ClientCache;
            return obj;
        }


        $scope.openChildWinMonitoringNotes = function (monitoringRecordNotesIndex, record, Url) {

            MonitoringRecordsRowIndex = monitoringRecordNotesIndex;
            Notesflag = null;
            var recordIdentifier;
            if (record.MonitoringTypeAbbr == "BE") {
                recordIdentifier = record.EntityId;
            }
            else
                recordIdentifier = record.RecordIdentifier;

            var monitorNotesValues = {
                'RecordIdentifier': recordIdentifier,
                'MonitoringType': record.MonitoringTypeAbbr,
                'StCode': record.StateCode
            };
            monitoringPortfolioService.InsertMonitoringNotes(monitorNotesValues).then(function (response) {
                if (response.statusText = "OK") {
                    $scope.UpdateCache();
                    var MessageId = response.data;
                    var MonitorNotesURL = Url + MessageId;
                    var mywindow = window.open(MonitorNotesURL, "MonitorNotesWindow", "resizable=yes,scrollbars=yes,top=5,left=5,width=940,height=550");
                    if (mywindow.opener == null)
                        mywindow.opener = self;
                    return false;

                } else {
                    notificationService.flash({
                        type: toasterNotificationTypeError, title: "Monitor Notes not Updated", body: " changes have not been saved."
                    });
                }

            })
           .catch(function (response) {
               busywindow.close();
           });
        }

        $scope.OpenAlertNotes = function (messageId, url) {
            url += messageId;
            var mywindow = window.open(url, "NotesWindow", "resizable=yes,scrollbars=yes,top=5,left=5,width=940,height=550");
            if (mywindow.opener == null)
                mywindow.opener = self;
        }

        $scope.openChildWinAlertNotes = function (monitoringAlertRowIndex, records, Url) {
            MessageID = '';
            monitoringAlertNotesIndex = '';
            monitoringAlertNotesIndex = monitoringAlertRowIndex;
            MessageID = records.MessageId;
            $scope.OpenAlertNotes(records.MessageId, Url);
            return false;
        }

        $scope.openChildAlertDetailNotes = function (records, Url, ChangeType, rowindex) {
            Notesflag = ChangeType;
            monitoringAlertNotesIndex = rowindex;
            MessageID = records.MessageId;
            $scope.OpenAlertNotes(records.MessageId, Url);
            return false;

        }

        $scope.clearSearchInput = function () {
            $scope.ClearSearchUserInput();
            document.getElementById("advancedSearchFromId").value = '';
            document.getElementById("advancedSearchToId").value = '';
            $scope.StartDate = undefined;
            $scope.EndDate = undefined;
        };

        $scope.ClearSearchUserInput = function () {
            $scope.EntitySearchId = '';
        };
        $scope.changeMonitoringGridPageSize = function () {
            $scope.MonitoringRecordsGridCurrentPage = 1;
            $scope.MonitoringRecordsGridPageSize = $scope.monitoringGridSelectedPageSize;
            $scope.searchRecords(false, false);
        };

        $scope.GetTimeFrameBy = function (currentGrid) {

            var GetTimeFrameByObj = monitoringPortfolioService.GetPreferenceValue();
            GetTimeFrameByObj.then(function (response) {
                var prefVal = "Last Month";
                for (var i = 0; i < response.data.length; i++) {
                    if (response.data[i].PrefName === "TimeFrameBy") {
                        prefVal = response.data[i].PrefVal;
                        if (prefVal == null || prefVal.length == 0)
                            prefVal = "Last Month";
                        continue;
                    }
                }
                if (currentGrid == ALERTGRID) {
                    $scope.RecentAlertTermCombo = prefVal;
                }
                else {
                    $scope.DateCombo = prefVal;
                }
            })
        };

        $scope.InitAdvancedSearchOptions = function () {
            $scope.ClearSearchUserInput();
            $scope.ResetPieChart();

            $scope.ActionCombo = $scope.searchActionPreferences[0];
            $scope.monitoringGridSelectedPageSize = $scope.monitoringGridPages[$scope.monitoringGridPages.length - 1];
            $scope.quickViewSelectionOptions = $scope.quickViewSelectionOption;

            if (!$scope.getIsUserAuthorized())
                $scope.quickViewSelectionOptions = $scope.quickViewSelectionReadOnlyOption;

            if ($scope.CurrentGrid == MONITORINGQGRID) { // QUICK VIEW => Full Monitoring Portfolio
                $scope.AdvancedSearchByMonitoringRecordStatus = $scope.MonitoringRecordStatusOptionsWithUnResolved;
                $scope.AdvancedSearchByMonitoringRecordTimeFrame = $scope.MonitoringRecordTimeFrameOptions;
                $scope.AdSearchByMonRecTypeSelection = $scope.AdSearchByMonRecType[0];
                $scope.AdvancedSearchByMonitoringRecordTimeFrameBy = $scope.MonitoringRecordTimeFrameByOptionsForFullMonitoringView;
            }
            else if ($scope.CurrentGrid == MONITORINGGRID) { // QUICK VIEW => Default View
                $scope.AdvancedSearchByMonitoringRecordStatus = $scope.MonitoringRecordStatusOptions;
                $scope.AdvancedSearchByMonitoringRecordTimeFrame = $scope.MonitoringRecordTimeFrameOptions;
                $scope.AdvancedSearchByMonitoringRecordTimeFrameBy = $scope.MonitoringRecordTimeFrameByOptions;
                $scope.AdSearchByMonRecTypeSelection = null;
            }
            else if ($scope.CurrentGrid == ALERTGRID) {// QUICK VIEW => Alerts Only
                $scope.AdvancedSearchByMonitoringRecordTimeFrame = $scope.MonitoringRecordTimeFrameOptions;
                $scope.AdvancedSearchByMonitoringRecordTimeFrameBy = $scope.MonitoringRecordTimeFrameByOptions;
                $scope.RecentAlertDateCombo = $scope.searchRecentAlertDatePreferences[0];
                $scope.RecentAlertStatusCombo = $scope.searchRecentAlertStatusPreferences[0];
                $scope.RecentAlertTypeCombo = $scope.searchRecentAlertTypePreferences[0];
                $scope.GetTimeFrameBy($scope.CurrentGrid);              
            }
            else if ($scope.CurrentGrid == INACTIVERECORDGRID) { // QUICK VIEW => Inactive Monitoring Portfolio
                $scope.AdvancedSearchByMonitoringRecordStatus = $scope.MonitoringRecordStatusOptions;
                $scope.AdvancedSearchByMonitoringRecordTimeFrame = $scope.MonitoringRecordTimeFrameOptions;
                $scope.AdvancedSearchByMonitoringRecordTimeFrameBy = $scope.MonitoringRecordTimeFrameByOptions;
                $scope.AdSearchByMonRecTypeSelection = $scope.AdSearchByMonRecType[0];
                $scope.StatusCombo = $scope.AdvancedSearchByMonitoringRecordStatus[0];
                $scope.TimeframeCombo = $scope.AdvancedSearchByMonitoringRecordTimeFrame[1];
                $scope.GetTimeFrameBy($scope.CurrentGrid);                
            }
            if ($scope.CurrentGrid == MONITORINGQGRID || $scope.CurrentGrid == MONITORINGGRID) {
                $scope.StatusCombo = $scope.AdvancedSearchByMonitoringRecordStatus[0];
                $scope.TimeframeCombo = $scope.AdvancedSearchByMonitoringRecordTimeFrame[0];
                $scope.GetTimeFrameBy($scope.CurrentGrid);              
            }
        }
        $scope.clearSearchOptions = function () {
            $scope.clearSearchInput();

        };
        $scope.getTransactHistory = function (alertId, monitoringRecordId) {

            return alertHistoryService.getTransactHistory(alertId, monitoringRecordId);
        }

        $scope.GetAlertDetail = function (monitoringRecordId) {
            $scope.messageID = monitoringRecordId;
            var result = alertService.GetAlertDetail(monitoringRecordId);
            return result;
        }

        $scope.getMonitoringTransactHistory = function (monitoringRecord) {
            return monitoringHistoryService.getMonitoringTransactHistory(monitoringRecord);

        };


        $scope.recentAlertsPaginaton = function () {
            $scope.recentAlertsMaxPage = Math.ceil($scope.TotalAlertsInGrid / $scope.recentAlertsMaxPage);
            for (var i = 0; i < $scope.recentAlertsList.length; i++) {
                $scope.recentAlertsList[i].Expanded = false;
                $scope.recentAlertsList[i].CustomDivId = i;

            }
        }

        /*Recent 60 Days Alerts Stats*/
        $scope.SearchAlertsRecords = function () {

            var searchKeyLength = $("#search-key").val().length;
            if (searchKeyLength <= 2 && searchKeyLength > 0) {
                if ($scope.isToasterExists()) {
                    $scope.showFlashMessage(toasterNotificationTypeWarning, "Search Key Validation", "Please enter atleast 3 characters");
                }
                return;
            }
            if ($scope.enableCustomDate) {
                $scope.ValidateCustomDate(false);
            }
            if (!$scope.StartDateError && !$scope.EndDateError && !$scope.errorDate && !$scope.searkeyError) {

                $scope.identifySearch = "AlertsOnly";
                var busywindow = busyWaitService.Start();
                $scope.inializeRecentAlertsPager();
                var alertsOnlyCriteria = $scope.createAlertsOnlyCriteria($scope.days, $scope.currentPage, $scope.maxPageNum, 'AlertDate', $scope.isRecentAlertsSortAsc);
                $scope.getAlertsOnlyByService(alertsOnlyCriteria, busywindow);
            }
        };

        $scope.buildQuickView = function (RecentAlertCombo, lazyLoad) {

            $scope.identifySearch = "RecentAlerts";
            $scope.isAlertMailed = false;
            if (RecentAlertCombo == quickViewDefault || RecentAlertCombo == quickViewFullMonitoringView || RecentAlertCombo == quickViewInActiveRecord) {

                if (RecentAlertCombo == quickViewDefault) {
                    return;
                }
                else if (RecentAlertCombo == quickViewFullMonitoringView) {
                    $scope.CurrentGrid = MONITORINGQGRID;
                    $scope.enableCustomDate = false;
                    $scope.Portfolioexpandstatus = false;
                } else if (RecentAlertCombo == quickViewInActiveRecord) {
                    $scope.CurrentGrid = INACTIVERECORDGRID;
                    $scope.enableCustomDate = true;
                    $scope.Portfolioexpandstatus = true;
                }

                $scope.InitAdvancedSearchOptions();
                $scope.enablePortfolioSearchPanel = true;
                $scope.enableRecentAlertSearchPanel = false;
                $scope.enableOrDisableRecentAlerts(false);
                $scope.inializeRecentAlertsPager();
                $scope.enablePortFolioSearchCalendar = false;
                $scope.RecentAlertCombo = RecentAlertCombo;
                $scope.recentAlertsList = null;
                $scope.PerformTabEnableByparams(true, true, true, true);
                $scope.PerformTabHighLight("All");
                $scope.PerformTabNavigation("All");
                $scope.IsUnResolvedTabVisible = false;

                if (RecentAlertCombo == quickViewInActiveRecord) {
                    $scope.EndDate = new Date();
                    $scope.EndDate.setDate($scope.EndDate.getDate());
                    $scope.StartDate = new Date();
                    $scope.StartDate.setDate($scope.StartDate.getDate() - 30);
                    $scope.enablePortFolioSearchCalendar = false;
                    setTimeout(function () {
                        $scope.PortfolioExpandSearch();
                    }, 10);

                    $scope.searchRecords(true, false);
                }
                if (RecentAlertCombo == quickViewFullMonitoringView) {

                    setTimeout(function () {
                        $scope.PortfolioCollapseSearch();
                    }, 10);
                    if (!lazyLoad) {
                        $scope.searchRecords(true, false);
                    }

                }
            }
            else if (RecentAlertCombo == quickViewAlertsOnly) {
                $scope.Alertexpandstatus = true;
                $scope.CurrentGrid = ALERTGRID;
                $scope.InitAdvancedSearchOptions();
                $scope.RecentAlertCombo = RecentAlertCombo;
                $scope.enablePortfolioSearchPanel = false;
                $scope.enableRecentAlertSearchPanel = true;
                $scope.monitoringRecordsList = null;

                $scope.enableOrDisableRecentAlerts(true);
                $scope.inializeRecentAlertsPager();
                var defaultDaysForAlertsQuickView = 60;
                var criteria = $scope.createRecentAlertsCriteria($scope.days, $scope.currentPage, $scope.maxPageNum, 'AlertDate', $scope.isRecentAlertsSortAsc);
                $scope.EndDate = new Date();
                $scope.EndDate.setDate($scope.EndDate.getDate());
                $scope.StartDate = new Date();
                $scope.StartDate.setDate($scope.StartDate.getDate() - 60);
                $scope.enableCustomDate = true;
                $scope.enablePortFolioSearchCalendar = false;
                setTimeout(function () {
                    $scope.AlertExpandSearch();
                }, 10);
                if (lazyLoad) {
                    setTimeout(function () {
                        var busywindow = {};
                        var busywindow = busyWaitService.Start();
                        $scope.getRecentAlertsByService(criteria, busywindow);
                    }, 1000);

                }
                else {
                    var busywindow = {};
                    var busywindow = busyWaitService.Start();
                    $scope.getRecentAlertsByService(criteria, busywindow);
                }
            }
        };

        /******** pagination-angular ************/
        $scope.inializeRecentAlertsPager = function () {
            $scope.recentAlertsSortKey = 'AlertDate';
            $scope.isRecentAlertsSortAsc = false;
            $scope.recentAlertsSearchCriteria = {
            };
            $scope.days = 0;
            $scope.recentAlertsMaxPage = 50;
            $scope.recentAlertsSelectedPageSize = $scope.recentAlertsPageSize[$scope.recentAlertsPageSize.length - 1];
            $scope.maxPageNum = 50;
            $scope.TotalAlertsInGrid = 0;
            $scope.recentAlertsList = [];

            $scope.totalItems = 0;
            $scope.currentPage = 1;
            $scope.maxSize = 1;
        }
        /******** pagination-angular ************/
        $scope.inializeRecentAlertsPager();
        $scope.sortRecentAlertsByColumn = function (sortColumn) {
            if ($scope.recentAlertsSearchCriteria == null) return;

            if ($scope.recentAlertsList.length < 2) return; // Enable sorting only when there are atleast 2 records in the grid

            if ($scope.recentAlertsSortKey !== sortColumn) {
                $scope.isRecentAlertsSortAsc = false;
            } else {
                $scope.isRecentAlertsSortAsc = !$scope.isRecentAlertsSortAsc;
            }
            $scope.recentAlertsSortKey = sortColumn;
            $scope.callAlertsModalService();

        }
        $scope.isDisableIfRecordsLessthanPageSize = function () {
            return $scope.TotalAlertsInGrid < $scope.recentAlertsSelectedPageSize;
            //return $scope.TotalAlertsInGrid < $scope.recentAlertsSelectedPageSize && ($scope.recentAlertSelectedPageSize <=10); (Alternate method)
        }
        $scope.getAlertsOnlyByService = function (SearchCriteria, busywindow) {
            var alertsOnly = monitoringPortfolioService.getAlertsOnlyDetails(SearchCriteria);
            alertsOnly.then(function (res) {
                if (res.data.Records.length == 0) $scope.showFlashMessage(toasterNotificationTypeInfo, "Search Results", "There are no records under this Alerts Portfolio view.");
                $scope.TotalAlertsInGrid = res.data.Count; //res.data.Records.length;
                $scope.totalItems = res.data.Count; //res.data.Records.length;
                $scope.recentAlertsList = res.data.Records;
                busywindow.close();
            }, function (res) {
                busywindow.close();
                $scope.recentAlertsList = null;
                $scope.monitoringRecordsList = null;
            });
        };
        $scope.getRecentAlertsByService = function (SearchCriteria, busywindow) {

            var RecentAlertData = monitoringPortfolioService.getRecentAlertsDetails(SearchCriteria);
            RecentAlertData.then(function (res) {
                if (res.data.Records.length == 0) $scope.showFlashMessage(toasterNotificationTypeInfo, "Search Results", "There are no records under this Alerts Portfolio view.");

                $scope.TotalAlertsInGrid = res.data.Count; //res.data.Records.length;
                $scope.totalItems = res.data.Count; //res.data.Records.length;
                $scope.recentAlertsList = res.data.Records;
                busywindow.close();
            }, function (res) {
                busywindow.close();
                $scope.recentAlertsList = null;
                $scope.monitoringRecordsList = null;
            });
        };
        $scope.createAlertsOnlyCriteria = function (days, currentPage, maxPageNum, sortColumn, IsAscending) {

            $scope.alertsOnlySearchCriteria = {
                "SearchKey": $scope.EntitySearchId,
                "TimeFrameBy": $scope.RecentAlertDateCombo.replace(/\s+/g, ""),
                "TimeFrame": $scope.RecentAlertTermCombo.replace(/\s+/g, ""),
                "Status": $scope.RecentAlertStatusCombo,
                "TimeframeFromStr": $scope.ResolveDate($scope.StartDate),
                "TimeframeToStr": $scope.ResolveDate($scope.EndDate),
                "filetype": $scope.RecentAlertTypeCombo,
                "PageNumber": $scope.currentPage,
                "PageSize": $scope.maxPageNum,
                "SortColumn": sortColumn,
                "IsAscending": IsAscending,
                "RecentAlertBy": $scope.RecentAlertCombo,
                "IsReadStats": false,
                "fileTypeOptions": $scope.searchRecentAlertTypePreferences,
                "isAlertMailed": $scope.isAlertMailed
            }
            return $scope.alertsOnlySearchCriteria;
        }

        $scope.createRecentAlertsCriteria = function (days, currentPage, maxPageNum, sortColumn, IsAscending) {
            $scope.recentAlertsSearchCriteria.days = $scope.days;
            $scope.recentAlertsSearchCriteria.PageNumber = $scope.currentPage;
            $scope.recentAlertsSearchCriteria.PageSize = $scope.maxPageNum;
            $scope.recentAlertsSearchCriteria.SortColumn = sortColumn;
            $scope.recentAlertsSearchCriteria.IsAscending = IsAscending;
            $scope.recentAlertsSearchCriteria.isAlertMailed = $scope.isAlertMailed;
            return $scope.recentAlertsSearchCriteria;
        }

        $scope.pageChanged = function () {
            if ($scope.recentAlertsSearchCriteria == null) return;
            $scope.callAlertsModalService();

        };

        $scope.jumpPage = function (pn) {
            $scope.currentPage = pn;
            if ($scope.recentAlertsSearchCriteria == null) return;
            $scope.callAlertsModalService();

        };
        $scope.changeRecentAlertsPageSize = function () {

            $scope.recentAlertsMaxPage = $scope.recentAlertsSelectedPageSize;
            $scope.recentAlertsPaginaton();
            $scope.maxPageNum = $scope.recentAlertsSelectedPageSize;
            $scope.callAlertsModalService();


        };
        $scope.callAlertsModalService = function () {
            var busywindow = busyWaitService.Start();
            if ($scope.identifySearch == 'RecentAlerts') {
                var criteria = $scope.createRecentAlertsCriteria($scope.days, $scope.currentPage, $scope.maxPageNum, $scope.recentAlertsSortKey, $scope.isRecentAlertsSortAsc);
                $scope.getRecentAlertsByService(criteria, busywindow);
            } else if ($scope.identifySearch == 'AlertsOnly') {
                var alertsOnlyCriteria = $scope.createAlertsOnlyCriteria($scope.days, $scope.currentPage, $scope.maxPageNum, $scope.recentAlertsSortKey, $scope.isRecentAlertsSortAsc);
                $scope.getAlertsOnlyByService(alertsOnlyCriteria, busywindow);
            }
        };

        /*ActiveStatusRecord Ends*/

        /************************  modal data ******************************/
        $scope.animationsEnabled = false;

        $scope.alertTransactionHistoryModal = function (alertId) {
            var busywindow = busyWaitService.Start();
            var modalInstance = $uibModal.open({
                animation: $scope.animationsEnabled,
                templateUrl: "TransactHistoryAlertModal.html",
                controller: "ModalInstanceCtrl",
                backdrop: "static",
                size: 'md6',
                resolve: {
                    transactHistoryAlertList: function ($q) {

                        var deferred = $q.defer();
                        var trHistory = $scope.getTransactHistory(alertId);
                        trHistory.then(function (response) {
                            busywindow.close();
                            deferred.resolve(response);
                        });
                        return deferred.promise;
                    }

                }
            });

            modalInstance.result.then(function (selectedItem) {
                $scope.selected = selectedItem;
            }, function () {
            });
        };
        /**********************Model Data********************/
        $scope.ResetUnResolvedRecords = function () {
            $scope.unresolvedHighlightedRowsArr = [];
        }
        $scope.OpenResolveWindow = function () {


            var CustomDivId = monitoringRecord.CustomDivId + 1;
            var unresolvedDetails = {};
            var unresolvedDebtorEntityIdFromModel = "unresolvedDebtorEntityIdFromModel" + CustomDivId;
            var EntityId = monitoringRecord.EntityId;
            var debtorNameColumn = "debtorNameColumn" + CustomDivId;
            unresolvedDetails.rowdata = monitoringRecord;
            unresolvedDetails.EntityId = EntityId;
            unresolvedDetails.debtorNameColumn = debtorNameColumn;
            unresolvedDetails.CustomDivId = CustomDivId - 1;
            unresolvedDetails.unresolvedDebtorEntityIdFromModel = unresolvedDebtorEntityIdFromModel;


            var modalInstance = $uibModal.open({
                animation: $scope.animationsEnabled,
                templateUrl: "UnresolveSearch.html",
                controller: "UnresolveSearchCtrl",
                backdrop: "static",
                resolve: {
                    unresolvedDetails: function () {
                        return unresolvedDetails;
                    }
                }
            });

            modalInstance.result.then(function (response) {
                angular.element('#' + response.unresolvedDebtorEntityIdFromModel).val(response.entryname);

                $scope.AddItemsToBeResolved(response.rowdata);
            }, function () {

            });
        };

        $scope.UpdateUnResolvedRecords = function () {
            $scope.UpdateCache();

            var records = tobeResolvedItems;
            if (records.length < 1) {
                notificationService.flash({
                    type: toasterNotificationTypeWarning, title: "Resolve Monitoring Records", body: "No record selected to resolve"
                });
                return
            }
            var busywindow = busyWaitService.Start();
            monitoringPortfolioService.ResolveRecords(records)
                      .then(function (response) {
                          ResetItemstoBeResolved();
                          var statusData = response.data.StatusChangeResult;
                          var numberOfRecordsResolved = 0;
                          for (var i = 0, len = statusData.length; i < len; i++) {
                              var found = $filter('filter')($scope.monitoringRecordsList, {
                                  RecordIndex: statusData[i].RecordIndex
                              }, true);
                              if (found.length > 0) {
                                  var stats = statusData[i].NewStatus;
                                  if (stats == "RESOLVED") {
                                      found[0].MonitoringStatusDesc = stats;
                                      numberOfRecordsResolved++;
                                      found[0].IsReadOnly = true;
                                  }
                                  else {
                                      found[0].StateError = '';
                                      found[0].Error = '';

                                      if (stats == "Invalid State") {
                                          ChangeStateCodeUserInput(statusData[i].RecordIndex, pinkColor);
                                          found[0].StateError = stats;
                                      }

                                      found[0].Error = stats;
                                      ChangeBusinessEnityUserInput(statusData[i].RecordIndex, pinkColor);
                                  }
                              }
                          }
                          notificationService.flash({
                              type: toasterNotificationTypeInfo, title: "Resolve Monitoring Records", body: numberOfRecordsResolved + " Record(s) successfully Resolved"
                          });

                          busywindow.close();

                          $scope.updateTabStaticticsOnUnResolve(response.data);
                          $scope.RefreshPieChart();


                      })
                      .catch(function (response) {
                          busywindow.close();
                      });
        }
        $scope.OpenMonitoringRecordNotes = function (record, url) {
            url += record.RecordIndex;
            var mywindow = window.open(url, "SearchWindow", "resizable=yes,scrollbars=yes,top=5,left=5,width=940,height=550");
            if (mywindow.opener == null)
                mywindow.opener = self;
        }
        $scope.openChildWinBusinessNameSearch = function (records, Url, currentrowId) {
            UnResvoledRecordBNSRowIndex = currentrowId;
            $scope.unresolvedCurrentRowItem = records;
            $scope.currentrowId = currentrowId;
            var name = records.DebtorName;
            var stcode = records.StateCode;
            var control = records.Entity;
            if (control != null && control != '') {
                control = null;
            }
            //  AcceptBnsEntityId('989898','','AL','');
            var UnresolvedURL = Url + control + "&SearchName=" + name + "&listJurisdictions=" + stcode;
            var mywindow = window.open(UnresolvedURL, "SearchWindow", "resizable=yes,scrollbars=yes,top=5,left=5,width=940,height=550");
            if (mywindow.opener == null)
                mywindow.opener = self;

            return false;
        }
        $scope.UpdateUnResolvedState = function (obj, rowId) {

            if (obj.StateCode.length < 1) {
                ChangeStateCodeUserInput(rowId, whiteColor);
                return;
            }
            ChangeStateCodeUserInput(rowId, yellowColor);
        }

        $scope.UpdateUnResolvedEntityId = function (obj, rowId) {

            if (obj.EntityId.length < 1) {
                ChangeBusinessEnityUserInput(rowId, whiteColor);
                return;
            }

            ChangeBusinessEnityUserInput(rowId, yellowColor);
            PopulateUnResolvedItems(rowId, true);
        }

        /************************  modal data ******************************/
        function loadAlertDetailsFlashMessage(debtorName) {
            notificationService.flash({ type: toasterNotificationTypeError, title: "Alert Details ", body: "No Data( Json format) found for : " + debtorName });
        }

        $scope.parseNameChangeData = function (response) {
            if (response.data.ChangeDetails == null) {
                loadAlertDetailsFlashMessage(response.data.DebtorName);
            }
            else {

                response.data.MessageId = response.data.MessageId;
                response.data.OldName = response.data.ChangeDetails.nameChangeAlert.OldName;
                response.data.NewName = response.data.ChangeDetails.nameChangeAlert.NewName;
                response.data.EntityID = response.data.RecordIdentifier;
                response.data.Status = response.data.Reviewed;
                delete response.data.ChangeDetails;
            }

        }

        $scope.parseStatusChangeData = function (response, debtorName) {
            if (response.data.ChangeDetails == null) {
                loadAlertDetailsFlashMessage(debtorName);
            } else {
                response.data.MessageId = response.data.MessageId;
                response.data.OldEntityStatus = response.data.ChangeDetails.statusChangeAlert.OldEntityStatus;
                response.data.NewEntityStatus = response.data.ChangeDetails.statusChangeAlert.NewEntityStatus;
                response.data.EntityId = response.data.RecordIdentifier;
                response.data.AlertStatus = response.data.Reviewed;
                response.data.DebtorName = debtorName;
                delete response.data.ChangeDetails;
            }
        }
        $scope.checkReviewed = function (response, monitoringType) {
            var reviewedType = '';
            angular.forEach(response, function (obj, key) {
                if (monitoringType == NameChange) {
                    reviewedType = obj.Status;
                } else if (monitoringType == StatusChange) {
                    reviewedType = obj.AlertStatus;
                } else if (monitoringType == UCC1 || monitoringType == UCC3) {
                    reviewedType = obj.AlertStatus;
                } else if (monitoringType == 'DebtorAlert') {
                    reviewedType = obj.Reviewed;
                }

                if (reviewedType == statusReviewed) {
                    obj.reviewChecked = true;
                } else if (reviewedType == statusNotReviewed) {
                    obj.reviewChecked = false;
                }

            });
        }

        $scope.OpenAlertDetailModal = function (monitoringRecordId, monitoringType, modalSize, debtorName, monitoringRecordAlerts) {
            var busywindow = busyWaitService.Start();
            var alertModalTemplate = '';
            if (monitoringType == NameChange) {
                alertModalTemplate = 'NameChangeAlertModal.html';
                modalSize = 'md3';
            } else if (monitoringType == StatusChange) {
                alertModalTemplate = 'statusChangeAlertModal.html';
                modalSize = 'md3';
            } else if (monitoringType == UCC1 || monitoringType == UCC3) {
                alertModalTemplate = 'UCCChangeAlertModal.html';
                modalSize = 'md4';
            }

            var modalInstance = $uibModal.open({
                animation: $scope.animationsEnabled,
                templateUrl: alertModalTemplate,
                scope: $scope,
                controller: "AlertModalInstance",
                size: modalSize,
                backdrop: "static",
                resolve: {
                    alertModalList: function ($q) {
                        var deferred = $q.defer();
                        var promise = $scope.GetAlertDetail(monitoringRecordId);
                        promise.then(function (response) {
                            if (monitoringType == NameChange) {
                                $scope.parseNameChangeData(response);
                                $scope.checkReviewed(response, monitoringType);
                            } else if (monitoringType == StatusChange) {
                                $scope.parseStatusChangeData(response, debtorName);
                                $scope.checkReviewed(response, monitoringType);
                            } else if (monitoringType == UCC1 || monitoringType == UCC3) {
                                $scope.parseUccChangeData(response, debtorName);
                                $scope.checkReviewed(response, monitoringType);
                            }

                            response.isUserAuthorized = $scope.isUserAuthorized;
                            response.monitoringRecordAlerts = monitoringRecordAlerts;

                            busywindow.close();
                            deferred.resolve(response);
                        });
                        return deferred.promise;


                    },
                    messageID: function () {
                        return monitoringRecordId;
                    },
                    searchActionPreferences: function () {
                        return $scope.searchActionPreferences;
                    }
                }
            });

            modalInstance.result.then(function (selectedItem) {
                $scope.selected = selectedItem;

            },

            function () {
            });

        };
        /* code modified by jana for UCC */

        $scope.parseUccChangeData = function (response, debtorName) {
            if (response.data.ChangeDetails == null) {
                loadAlertDetailsFlashMessage(debtorName);
            } else {
                response.data.MessageId = response.data.MessageId;
                response.data.UccType = response.data.ChangeDetails.uccChangeAlert.UccType;
                response.data.FileType = response.data.ChangeDetails.uccChangeAlert.FileType;
                response.data.FileNumber = response.data.ChangeDetails.uccChangeAlert.FileNumber;
                response.data.FileDate = response.data.ChangeDetails.uccChangeAlert.FileDate;
                response.data.AlertStatus = response.data.Reviewed;
                response.data.DebtorName = debtorName;
                delete response.data.ChangeDetails;
            }
        }



        /* code modified ended here by jana for UCC */

        $scope.OpenMonitoringTransactionHistory = function (monitoringRecord) {
            var busywindow = busyWaitService.Start();
            if (monitoringRecord.RecordIdentifier == undefined) {
                notificationService.flash({
                    type: toasterNotificationTypeInfo, title: "Transaction History", body: "Entity Information Unavailable"
                });
                return;
            }

            else if (monitoringRecord.RecordIdentifier.length == 0) {
                notificationService.flash({
                    type: toasterNotificationTypeInfo, title: "Transaction History", body: "Entity Information Unavailable"
                });
                return;
            }
            var debtor = {
                'RecordIdentifier': monitoringRecord.RecordIdentifier,
                'MonitoringType': monitoringRecord.MonitoringTypeAbbr,
                'StateCode': monitoringRecord.StateCode,
                'DebtorName': monitoringRecord.DebtorNameShort
            };
            var modalInstance = $uibModal.open({
                animation: $scope.animationsEnabled,
                templateUrl: "MonitoringTransactHistoryModal.html",
                controller: "MonitoringTransactionHistoryCtrl",
                size: 'md5',
                scope: $scope,
                backdrop: "static",
                resolve: {
                    monitoringTransactHistoryList: function ($q) {
                        var deferred = $q.defer();
                        var mtrHistory = $scope.getMonitoringTransactHistory(debtor);
                        mtrHistory.then(function (response) {
                            busywindow.close();
                            deferred.resolve(response);
                        });
                        return deferred.promise;
                    }
                }
            });

            modalInstance.result.then(function (selectedItem) {
                $scope.selected = selectedItem;
            }, function () {
            });
        };
        /*Monitoring Transactions Starts*/

        /************************  modal debtor Alert History ******************************/

        $rootScope.$on('OpenDebtorAlertHistoryFromChild', function (event, record) {
            $scope.OpenDebtorAlertHistory(record);
        });

        $scope.OpenDebtorAlertHistory = function (record) {

            if (record.Expanded) {
                record.Expanded = !record.Expanded;
                $scope.expandMonitoringRecord(record);
            }

            var busywindow = busyWaitService.Start();
            $scope.isGoBackEnable = false;
            var debtor = {
                'RecordIdentifier': record.RecordIdentifier,
                'MonitoringType': record.MonitoringTypeAbbr,
                'StateCode': record.StateCode,
                'DebtorName': record.DebtorName
            };
            var modalInstance = $uibModal.open({
                animation: $scope.animationsEnabled,
                templateUrl: "alertDebtorHistorymodal.html",
                controller: "ModalInstanceAlertCtrl",
                backdrop: "static",
                scope: $scope,
                size: 'md2',
                resolve: {
                    debtoralertHistoryList: function ($q) {
                        var deferred = $q.defer();
                        var debtorAlertsList = monitoringPortfolioService.getAlerts(debtor);

                        debtorAlertsList.then(function (alerts) {
                            $scope.checkReviewed(alerts.data._monitoringDebtorAlertDetails, 'DebtorAlert');
                            alerts.data.debtorSubgridData = $scope.holdDebtorExpandedData;
                            alerts.data.isDebtorExpanded = record.Expanded;
                            busywindow.close();
                            deferred.resolve(alerts);
                        });

                        return deferred.promise;

                    },
                    record: function () {
                        return record;
                    },
                    alertService: function () {
                        return alertService;
                    },
                    searchActionPreferences: function () {
                        return $scope.searchActionPreferences;
                    },
                    isGoBackEnable: function () {
                        return $scope.isGoBackEnable;
                    }
                }
            });

            modalInstance.result.then(function (selectedItem) {
                $scope.selected = selectedItem;
            }, function () {
                $rootScope.$broadcast('closeAllChildAlertModals', 'all');

            });
        };
        /************************  modal debtor Alert History ******************************/

        $scope.init();
        $scope.isInActiveTabColor = function (tab) {
            return tab.inactivetabcolor;
        }
        $scope.preventAnchor = function () {
            alert("prevent anchor");
        }

        $scope.portfolioHeaderTabs = [
        {
            name: monitoringRecordsStatusAll, activeclass: "LiResultBorder", method: $scope.getAllLoadedSearchRecords, inactivetabcolor: "LiResultBorder1"
        },
        {
            name: monitoringRecordsStatusActive, activeclass: "LiResultBorder", method: $scope.getActiveRecords, inactivetabcolor: "LiResultBorder2"
        },
        {
            name: monitoringRecordsStatusSuspended, activeclass: "LiResultBorder", method: $scope.getSuspendedRecords, inactivetabcolor: "LiResultBorder3"
        },
        {
            name: monitoringRecordsStatusUnresolved, activeclass: "LiResultBorder", method: $scope.getUnresolvedRecords, inactivetabcolor: "LiResultBorder4"
        },
        ];
        $scope.portfolioRecentAlertsHeaderTabs = [
        {
            name: monitoringRecordsStatusAll, activeclass: "LiResultBorder", inactivetabcolor: "LiResultBorder1"
        }
        ];
    }]);

angular.module("monitoringPortfolioApp").controller("UnresolveSearchCtrl", ['$scope', '$modalInstance', '$timeout', 'unresolvedDetails', function ($scope, $modalInstance, $timeout, unresolvedDetails) {


    $scope.EntityId = unresolvedDetails.EntityId;
    $scope.debtorNameColumn = unresolvedDetails.debtorNameColumn;
    $scope.CustomDivId = unresolvedDetails.CustomDivId;

    $scope.unresolvedDetails = unresolvedDetails;
    $scope.getUnresolvedEntityId = function () {
        var debtorname = angular.element('#' + unresolvedDetails.debtorNameColumn).text();
        $scope.result = {};


        $scope.result.elementId = unresolvedDetails.debtorNameColumn;
        $scope.result.EntityId = unresolvedDetails.EntityId;
        $scope.result.unresolvedDebtorEntityIdFromModel = unresolvedDetails.unresolvedDebtorEntityIdFromModel;
        $scope.result.entryname = $scope.unresolvedDebtorName;
        $scope.result.debtorname = debtorname;
        $scope.result.CustomDivId = unresolvedDetails.CustomDivId;

        unresolvedDetails.rowdata.modalentryname = $scope.unresolvedDebtorName;
        $scope.result.rowdata = unresolvedDetails.rowdata;

        $scope.result.output = "mismatch";
        $modalInstance.close($scope.result);

    };

    $scope.cancel = function () {
        $modalInstance.dismiss("cancel");

    };

    $timeout(function () {
        $(".modal-content").draggable({
            handle: '.draggableSection, .bottom-drag', containment: ".modal-backdrop", scroll: false
        });

        var resizeOpts = {
            handles: "all",
            autoHide: true
        };

        $(".modal-content").resizable(resizeOpts);
    }, 0);

}]);



angular.module("monitoringPortfolioApp").controller("ModalInstanceCtrl", ['$scope', '$modalInstance', '$timeout', 'transactHistoryAlertList', function ($scope, $modalInstance, $timeout, transactHistoryAlertList) {


    $scope.TransactHistoryAlertList = transactHistoryAlertList.data;
    $scope.ok = function () {
        $modalInstance.close();
    };

    $scope.cancel = function () {
        $modalInstance.dismiss("cancel");
    };

    $timeout(function () {
        $(".modal-content").draggable({
            handle: '.draggableSection, .bottom-drag', containment: ".modal-backdrop", scroll: false
        });

        var resizeOpts = {
            handles: "all",
            autoHide: true
        };

        $(".modal-content").resizable(resizeOpts);
    }, 0);


}]);
/* code modified begin by janarthanan */
angular.module("monitoringPortfolioApp").controller("AlertModalInstance", ['$scope', '$modalInstance', '$timeout', 'alertModalList', 'searchActionPreferences', 'monitoringPortfolioService', 'messageID', 'notificationService', 'busyWaitService', function ($scope, $modalInstance, $timeout, alertModalList, searchActionPreferences, monitoringPortfolioService, messageID, notificationService, busyWaitService) {

    // OpenAlertDetailModal function
    $scope.UpdateMonitoringAlertStatus = function (record, statusType, newStatus, isAlertHistory, monitoringRecordAlerts) {
        var busywindow = busyWaitService.Start();
        if (newStatus == statusReviewed) {
            newStatus = statusNotReviewed;
        } else {
            newStatus = statusReviewed;
        }
        var alertChangeStatus = {
            'MessageId': record,
            'AlertStatus': newStatus,
            'ChangeType': statusType
        };

        monitoringPortfolioService.UpdateMonitoringAlertStatus(alertChangeStatus).then(function (response) {
            if (response.data === true) {
                busywindow.close();
                if (isAlertHistory == fromAlertHistory) monitoringRecordAlerts.Reviewed = newStatus;

                if (isAlertHistory == fromAlertDetails && statusType == StatusChange) monitoringRecordAlerts.AlertStatus = newStatus;
                if (isAlertHistory == fromAlertDetails && statusType == NameChange) monitoringRecordAlerts.Status = newStatus;
                if (isAlertHistory == fromAlertDetails && (statusType == UCC1 || statusType == UCC3)) monitoringRecordAlerts.AlertStatus = newStatus;

                alertModalList.monitoringRecordAlerts.Reviewed = newStatus;


                notificationService.flash({
                    type: toasterNotificationTypeSuccess, title: "Status Update", body: "Your changes have been saved."
                });


            } else {
                notificationService.flash({
                    type: toasterNotificationTypeError, title: "Status Update", body: "Your changes have not been saved."
                });
            }
        })
.catch(function (response) {
    busywindow.close();
});

    };


    var alertList = [];
    alertList.push(alertModalList.data);
    $scope.ActionCombo = searchActionPreferences[0];
    $scope.alertModalList = alertList;
    $scope.searchActionPreferences = searchActionPreferences;
    $scope.isUserAuthorized = alertModalList.isUserAuthorized;

    $scope.openChildWinAlertNotes = function (records, Url, ChangeType) {
        MessageID = '';
        monitoringAlertNotesIndex = '';
        Notesflag = ChangeType;
        MessageID = records.MessageId;
        $scope.OpenAlertNotes(records.MessageId, Url);
        return false;
    }

    $scope.OpenAlertNotes = function (messageId, url) {
        url += messageId;
        var mywindow = window.open(url, "NotesWindow", "resizable=yes,scrollbars=yes,top=5,left=5,width=940,height=550");
        if (mywindow.opener == null)
            mywindow.opener = self;
    }
    $scope.ok = function () {
        $modalInstance.close();
    };

    $scope.cancel = function () {
        $modalInstance.dismiss("cancel");
        $timeout(function () {
            $scope.cancel();
        }, 1);
    };
    $timeout(function () {
        $(".modal-content").draggable({ handle: '.draggableSection, .bottom-drag', containment: ".modal-backdrop", scroll: false });

        var resizeOpts = {
            handles: "all",
            autoHide: true
        };

        $(".modal-content").resizable(resizeOpts);
    }, 0);

}]);

/*Monitoring Transactions Starts*/

angular.module("monitoringPortfolioApp").controller("MonitoringTransactionHistoryCtrl", ['$scope', '$modalInstance', '$timeout', 'monitoringTransactHistoryList', function ($scope, $modalInstance, $timeout, monitoringTransactHistoryList) {


    $scope.MonitoringTransactHistoryList = monitoringTransactHistoryList.data;

    $scope.ok = function () {
        $modalInstance.close();
    };
    $scope.cancel = function () {
        $modalInstance.dismiss("cancel");
    };
    $timeout(function () {
        $(".modal-content").draggable({ handle: '.draggableSection, .bottom-drag', containment: "body" });

        var resizeOpts = {
            handles: "all",
            autoHide: true
        };

        $(".modal-content").resizable(resizeOpts);
    }, 0);


}]);

angular.module("monitoringPortfolioApp").controller("ModalInstanceAlertCtrl", ['$scope', '$filter', '$modalInstance', 'alertService', 'monitoringPortfolioService', '$timeout', '$q', '$log', 'searchActionPreferences', 'debtoralertHistoryList', '$uibModal', 'record', 'busyWaitService', 'notificationService', 'isGoBackEnable', function ($scope, $filter, $modalInstance, alertService, monitoringPortfolioService, $timeout, $q, $log, searchActionPreferences, debtoralertHistoryList, $uibModal, record, busyWaitService, notificationService, isGoBackEnable) {

    $scope.alertModalList = [];
    $scope.debtoralertHistoryList = debtoralertHistoryList.data;
    $scope.alertModalList.push(debtoralertHistoryList.data);

    $scope.searchActionPreferences = searchActionPreferences;
    $scope.record = record;
    $scope.isGoBackEnable = isGoBackEnable;

    $scope.$on('closeAllChildAlertModals', function (event, record) {
        $scope.cancel();
    });
    $scope.ok = function () {
        $modalInstance.close();
    };

    $scope.cancel = function () {
        $modalInstance.dismiss("cancel-all");
    };
    $scope.childModalCancel = function () {
        $modalInstance.dismiss("cancel");
    };
    $scope.parseNameChangeData = function (response, monitoringRecordId) {
        if (response.data.ChangeDetails == null) {
            loadAlertDetailsFlashMessage(response.data.DebtorName);
        }
        else {
            response.data.MessageId = monitoringRecordId;
            response.data.OldName = response.data.ChangeDetails.nameChangeAlert.OldName;
            response.data.NewName = response.data.ChangeDetails.nameChangeAlert.NewName;
            response.data.EntityID = response.data.RecordIdentifier;
            response.data.Status = response.data.Reviewed;
            delete response.data.ChangeDetails;
        }

    }

    $scope.parseStatusChangeData = function (response, debtorName) {

        if (response.data.ChangeDetails == null) {
            loadAlertDetailsFlashMessage(debtorName);
        } else {
            response.data.MessageId = response.data.MessageId;
            response.data.OldEntityStatus = response.data.ChangeDetails.statusChangeAlert.OldEntityStatus;
            response.data.NewEntityStatus = response.data.ChangeDetails.statusChangeAlert.NewEntityStatus;
            response.data.EntityId = response.data.RecordIdentifier;
            response.data.AlertStatus = response.data.Reviewed;
            response.data.DebtorName = debtorName;
            delete response.data.ChangeDetails;
        }

    }

    $scope.parseUccChangeData = function (response, debtorName) {
        if (response.data.ChangeDetails == null) {
            loadAlertDetailsFlashMessage(debtorName);
        } else {

            response.data.MessageId = response.data.MessageId;
            response.data.UccType = response.data.ChangeDetails.uccChangeAlert.UccType;
            response.data.FileType = response.data.ChangeDetails.uccChangeAlert.FileType;
            response.data.FileNumber = response.data.ChangeDetails.uccChangeAlert.FileNumber;
            response.data.FileDate = response.data.ChangeDetails.uccChangeAlert.FileDate;
            response.data.AlertStatus = response.data.Reviewed;
            response.data.DebtorName = debtorName;
            delete response.data.ChangeDetails;
        }
    }


    $scope.OpenAlertDetailChildModal = function (monitoringRecordId, monitoringType, modalSize, debtorName, isGoBackEnable, alertHistory) {
        var busywindow = busyWaitService.Start();
        var alertModalTemplate = '';

        if (monitoringType == NameChange) {
            alertModalTemplate = 'NameChangeAlertModal.html';
            modalSize = 'md3';
        } else if (monitoringType == StatusChange) {
            alertModalTemplate = 'statusChangeAlertModal.html';
            modalSize = 'md3';
        } else if (monitoringType == UCC1 || monitoringType == UCC3) {
            alertModalTemplate = 'UCCChangeAlertModal.html';
            modalSize = 'md4';
        }

        var modalInstance = $uibModal.open({
            animation: false,
            // templateUrl: 'UCCChangeAlertChildModal.html',
            templateUrl: alertModalTemplate,
            controller: "ModalInstanceAlertCtrl",
            size: modalSize,
            backdrop: "static",
            resolve: {
                debtoralertHistoryList: function ($q) {
                    var deferred = $q.defer();
                    var promise = alertService.GetAlertDetail(monitoringRecordId);
                    promise.then(function (response) {
                        response.data.readOnly = !$scope.IsUserActionEnabled();
                        if (monitoringType == NameChange) {
                            //parse data according to name change
                            $scope.parseNameChangeData(response, monitoringRecordId);
                            $scope.checkReviewed(response, monitoringType);
                        } else if (monitoringType == StatusChange) {
                            $scope.parseStatusChangeData(response, debtorName);
                            $scope.checkReviewed(response, monitoringType);
                        } else if (monitoringType == UCC1 || monitoringType == UCC3) {
                            $scope.parseUccChangeData(response, debtorName);
                            $scope.checkReviewed(response, monitoringType);
                        }
                        response.data.debtorSubgridData = debtoralertHistoryList.data.debtorSubgridData;
                        response.data.isDebtorExpanded = debtoralertHistoryList.data.isDebtorExpanded;
                        $scope.cancel();
                        deferred.resolve(response);
                        busywindow.close();
                    });
                    return deferred.promise;
                },
                record: function () {
                    return $scope.record;
                },
                searchActionPreferences: function () {
                    return $scope.searchActionPreferences;
                },
                isGoBackEnable: function () {
                    return isGoBackEnable;
                }
            }
        });
        modalInstance.result.then(function (selectedItem) {
            $scope.selected = selectedItem;
        }, function (close) {
            if (close == 'cancel-all') {
                $timeout(function () {
                    $scope.cancel();
                }, 0);
            }
        });


    };


    $scope.OpenAlertNotes = function (messageId, url) {
        url += messageId;
        var mywindow = window.open(url, "NotesWindow", "resizable=yes,scrollbars=yes,top=5,left=5,width=940,height=550");
        if (mywindow.opener == null)
            mywindow.opener = self;
    }

    $scope.openChildWinAlertNotes = function (records, Url, ChangeType) {
        MessageID = '';
        monitoringAlertNotesIndex = '';
        Notesflag = ChangeType;
        MessageID = records.MessageId;
        $scope.OpenAlertNotes(records.MessageId, Url);
        return false;
    }

    $scope.openChildDebtorAlertNotes = function (records, Url, ChangeType, rowindex) {
        MessageID = '';
        monitoringAlertNotesIndex = '';
        Notesflag = ChangeType;
        MessageID = records.MessageId;
        monitoringAlertNotesIndex = rowindex;
        $scope.OpenAlertNotes(records.MessageId, Url);
        return false;
    }

    $scope.checkReviewed = function (response, monitoringType) {

        var reviewedType = '';
        angular.forEach(response, function (obj, key) {
            if (monitoringType == NameChange) {
                reviewedType = obj.Status;
            } else if (monitoringType == StatusChange) {
                reviewedType = obj.AlertStatus;
            } else if (monitoringType == UCC1 || monitoringType == UCC3) {
                reviewedType = obj.AlertStatus;
            } else if (monitoringType == 'DebtorAlert') {
                reviewedType = obj.Reviewed;
            }

            if (reviewedType == statusReviewed) {
                obj.reviewChecked = true;
            } else if (reviewedType == statusNotReviewed) {
                obj.reviewChecked = false;
            }

        });
    }

    $scope.updateParentSubgridStatus = function (MessageId, newStatus) {
        if (!debtoralertHistoryList.data.isDebtorExpanded) return;
        var newobj = $filter("filter")(debtoralertHistoryList.data.debtorSubgridData, { MessageId: MessageId })[0].Reviewed = newStatus;
    }
    //OpenAlertDetailChildModal function
    $scope.UpdateMonitoringAlertStatus = function (record, statusType, newStatus, isAlertHistory, monitoringRecordAlerts) {

        var busywindow = busyWaitService.Start();
        if (newStatus == statusReviewed) {
            newStatus = statusNotReviewed;
        } else {
            newStatus = statusReviewed;
        }

        var alertChangeStatus = {
            'MessageId': record,
            'AlertStatus': newStatus,
            'ChangeType': statusType
        };

        monitoringPortfolioService.UpdateMonitoringAlertStatus(alertChangeStatus).then(function (response) {
            if (response.data === true) {
                if (isAlertHistory == fromAlertHistory) monitoringRecordAlerts.Reviewed = newStatus;

                if (isAlertHistory == fromAlertDetails && statusType == StatusChange) monitoringRecordAlerts.AlertStatus = newStatus;
                if (isAlertHistory == fromAlertDetails && statusType == NameChange) monitoringRecordAlerts.Status = newStatus;
                if (isAlertHistory == fromAlertDetails && (statusType == UCC1 || statusType == UCC3)) monitoringRecordAlerts.AlertStatus = newStatus;

                $scope.updateParentSubgridStatus(record, newStatus);

                notificationService.flash({
                    type: toasterNotificationTypeSuccess, title: "Status Update", body: "Your changes have been saved."
                });

                $scope.checkReviewed(monitoringRecordAlerts.data, 'DebtorAlert');

            } else {
                notificationService.flash({
                    type: toasterNotificationTypeError, title: "Status Update", body: "Your changes have not been saved."
                });
            }
            busywindow.close();
        })
            .catch(function (response) {
                busywindow.close();
            });

    };
    $scope.openParentAlertModal = function (record) {
        $scope.$emit('OpenDebtorAlertHistoryFromChild', record);
        $timeout(function () {
            $scope.cancel();
        }, 1);
    }



    $scope.goBackToDebtorType = function () {
        $modalInstance.dismiss("go-back");
    }

    $timeout(function () {
        $(".modal-content").draggable({
            handle: '.draggableSection, .bottom-drag', containment: ".modal-backdrop", scroll: false
        });

        var resizeOpts = {
            handles: "all",
            autoHide: true
        };

        $(".modal-content").resizable(resizeOpts);
    }, 0);

}]);

var tobeResolvedItems = [];

function ResetItemstoBeResolved() {
    tobeResolvedItems = [];
}

// Javascript function for business entity func calling by iLien application
function AcceptBnsEntityId(sEntityId, sEntityName, sState, sIncorpState) {

    var state = sState;

    if (sEntityId != null && sEntityId != '') {
        // Entity Id 
        UpdateDomTextBox('highlightRow' + UnResvoledRecordBNSRowIndex, sEntityId);
        ChangeBusinessEnityUserInput(UnResvoledRecordBNSRowIndex, brightyellowColor);

        // State Code
        var existingState = ReadFromdDomTextBox('highlightRowStateCode' + UnResvoledRecordBNSRowIndex);
        if (existingState != state) {
            UpdateDomTextBox('highlightRowStateCode' + UnResvoledRecordBNSRowIndex, state);
            ChangeStateCodeUserInput(UnResvoledRecordBNSRowIndex, brightyellowColor);
        }
        PopulateUnResolvedItems(UnResvoledRecordBNSRowIndex, false);
        UnResvoledRecordBNSRowIndex = '';
    }
}
function ChangeBusinessEnityUserInput(index, color) {
    document.getElementById('highlightRow' + index).style.backgroundColor = color;
}
function ChangeStateCodeUserInput(index, color) {
    document.getElementById('highlightRowStateCode' + index).style.backgroundColor = color;
}

function ReadFromdDom(element) {
    return document.getElementById(element).innerText.replace(/^\s+|\s+$/gm, '');
}
function ReadFromdDomTextBox(element) {
    return document.getElementById(element).value.replace(/^\s+|\s+$/gm, '');
}

function UpdateDomReadOnly(element, boolval) {
    document.getElementById(element).readOnly = boolval;
}

function UpdateDomTextBox(element, text) {
    return document.getElementById(element).value = text;
}

function PopulateUnResolvedItems(recordId, IsUser) {

    var stateCode = ReadFromdDomTextBox('highlightRowStateCode' + recordId);
    var newEntityId = ReadFromdDomTextBox('highlightRow' + recordId);

    var compressName = ReadFromdDom('SearchGridMonitoringRecordId' + recordId);
    var typecode = ReadFromdDom('SearchGridMonTypeCodeId' + recordId);
    var debtorName = ReadFromdDom('debtorNameColumn' + recordId);
    var lienrefNumber = ReadFromdDom('SearchGridMonitoringRecordLienRefNumber' + recordId);
    var debtorSequenceNumber = ReadFromdDom('SearchGridMonitoringRecordDebtorSequence' + recordId);


    var totalItems = tobeResolvedItems.length;
    if (totalItems > 0) {
        for (var i = 0; i < totalItems; i++) {
            if (tobeResolvedItems[i].RecordIndex == recordId) {
                tobeResolvedItems.splice(i, 1);
            }
        }
    }
    tobeResolvedItems.push({
        IsManualEnityUpdate: IsUser, LienRefNumber: lienrefNumber, DebtorSequence: debtorSequenceNumber, DebtorName: debtorName, MonitoringType: typecode, RecordIndex: recordId, RecordIdentifier: compressName, StateCode: stateCode, ResolvedEntityId: newEntityId
    });
}
/*
   ILIEN INTEGRATION
*/
function ChangeIconByCount(elementName, status) {
    var element = document.getElementById(elementName);
    if (element != null) {
        if (status == 1) {
            // element.src = "../Images/note.gif";
            element.setAttribute("wk-icon", "tooltip-text");
        }
        else {
            // element.src = "../Images/note_blank.gif";
            element.setAttribute("wk-icon", "tooltip");
        }
    }
}


function ChangeNotesIcon(keyNo, isNotePresent) {

    if (Notesflag == null) {
        var elementName = 'MonitoringRecordNotes' + MonitoringRecordsRowIndex;
        ChangeIconByCount(elementName, isNotePresent);
        //MonitoringRecordsRowIndex = null;
    } else {
        ChangeAlertResponseEventIcon(keyNo, isNotePresent);
    }
}



function ChangeAlertResponseEventIcon(keyNo, notesvalue) {
    if (monitoringAlertNotesIndex != null && monitoringAlertNotesIndex != '') {

        if (Notesflag == AlertStartingRef_DebtorHistory) {
            ChangeIconByCount('DebtorAlertGridActiveId_' + MessageID, notesvalue);
            ChangeIconByCount('AlertSubGridNotesId_' + MessageID, notesvalue);
        }
        else if (Notesflag == AlertStartingRef_RecentAlerts) {
            var elementName = 'recentAlertsListNotesId_' + MessageID;
            ChangeIconByCount(elementName, notesvalue);
        }
        else {
            var elementName = 'AlertSubGridNotesId_' + MessageID;
            ChangeIconByCount('DebtorAlertGridActiveId_' + MessageID, notesvalue);
            ChangeIconByCount(elementName, notesvalue);
        }
    }
    else {
        // pop up for BE and UCC
        if (Notesflag == NameChange) {
            ChangeIconByCount('NameChangeImgNotesId', notesvalue);
            ChangeIconByCount('AlertSubGridNotesId_' + MessageID, notesvalue);
            ChangeIconByCount('DebtorAlertGridActiveId_' + MessageID, notesvalue);
            ChangeIconByCount('recentAlertsListNotesId_' + MessageID, notesvalue);
        }
        if (Notesflag == StatusChange) {
            ChangeIconByCount('StatusChangeImgNotesId', notesvalue);
            ChangeIconByCount('AlertSubGridNotesId_' + MessageID, notesvalue);
            ChangeIconByCount('DebtorAlertGridActiveId_' + MessageID, notesvalue);
            ChangeIconByCount('recentAlertsListNotesId_' + MessageID, notesvalue);

        }
        if (Notesflag == NotesUCCflag) {
            ChangeIconByCount('UCCChangeImgNotesId', notesvalue);
            ChangeIconByCount('AlertSubGridNotesId_' + MessageID, notesvalue);
            ChangeIconByCount('DebtorAlertGridActiveId_' + MessageID, notesvalue);
            ChangeIconByCount('recentAlertsListNotesId_' + MessageID, notesvalue);
        }


    }
}
