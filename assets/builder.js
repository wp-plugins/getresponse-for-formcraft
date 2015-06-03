FormCraftApp.controller('GetResponseController', function($scope, $http) {
	$scope.addMap = function(){
		if ($scope.SelectedList=='' || $scope.SelectedColumn==''){return false;}
		if (typeof $scope.$parent.Addons.GetResponse.Map=='undefined')
		{
			$scope.$parent.Addons.GetResponse.Map = [];
		}
		$scope.$parent.Addons.GetResponse.Map.push({
			'listID': $scope.SelectedList,
			'listName': jQuery('#gr-map .select-list option:selected').text(),
			'columnID': $scope.SelectedColumn,
			'columnName': jQuery('#gr-map .select-column option:selected').text(),
			'formField': jQuery('#gr-map .select-field').val()
		});
	}
	$scope.removeMap = function ($index)
	{
		$scope.$parent.Addons.GetResponse.Map.splice($index, 1);
	}
	$scope.testKey = function(){
		jQuery('#gr-cover').addClass('loading');
		$http.get(FC.ajaxurl+'?action=formcraft_getresponse_test_api&key='+$scope.Addons.GetResponse.api_key).success(function(response){
			jQuery('#gr-cover').removeClass('loading');
			if (response.success)
			{
				$scope.$parent.Addons.GetResponse.validKey = $scope.Addons.GetResponse.api_key;
				jQuery('#gr-cover').addClass('loading');
				$http.get(FC.ajaxurl+'?action=formcraft_getresponse_get_lists&key='+$scope.Addons.GetResponse.validKey).success(function(response){
					jQuery('#gr-cover').removeClass('loading');
					if (response.success)
					{
						$scope.GRLists = response.lists;
						$scope.SelectedList = '';
					}
				});
			}
			else
			{
				$scope.$parent.Addons.GetResponse.validKey = false;
			}
		});
	}
	$scope.Init = function(){
		if ( typeof $scope.GRLists =='undefined' && typeof $scope.$parent.Addons!='undefined' && typeof $scope.$parent.Addons.GetResponse!='undefined' && typeof $scope.$parent.Addons.GetResponse.validKey!='undefined' && $scope.$parent.Addons.GetResponse.validKey!=false)
		{
			jQuery('#gr-cover').addClass('loading');
			$http.get(FC.ajaxurl+'?action=formcraft_getresponse_get_lists&key='+$scope.Addons.GetResponse.validKey).success(function(response){
				jQuery('#gr-cover').removeClass('loading');
				if (response.success)
				{
					$scope.GRLists = response.lists;
					$scope.SelectedList = '';
				}
			});
		}
	}
	$scope.$watch('SelectedList', function(){
		if (typeof $scope.$parent.Addons!='undefined' && $scope.SelectedList!='undefined' && $scope.SelectedList!='')
		{
			jQuery('#gr-cover').addClass('loading');
			$http.get(FC.ajaxurl+'?action=formcraft_getresponse_get_columns&key='+$scope.Addons.GetResponse.validKey+'&id='+$scope.SelectedList).success(function(response){
				jQuery('#gr-cover').removeClass('loading');
				if (response.success)
				{
					console.log(response.columns);
					$scope.GRColumns = response.columns;
					$scope.SelectedColumn = '';
				}
			});
		}
	});
	$scope.$watch('Addons.GetResponse.validKey', function(){
		if (typeof $scope.$parent.Addons!='undefined')
		{
			if (typeof $scope.$parent.Addons.GetResponse.validKey!='undefined' && $scope.$parent.Addons.GetResponse.validKey!=false)
			{
				$scope.$parent.Addons.GetResponse.showOptions = true;
			}
			else
			{
				$scope.$parent.Addons.GetResponse.showOptions = false;
			}
		}
	});
});