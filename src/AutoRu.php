<?php

class AutoRu {
/**
 * Функция парсинга сайта Auto.ru.
 * 
 * @param string $__pageCode Строка с адресом интернет-страницы с автомобилями выбранной марки и модели.
 * 
 * @return array
 */
	function ParsingAutoRu(string $__pageCode)
	{	
		set_time_limit(300);
		//Создаём и заполняем массив с информацией о названиях найденных автомобилей
		$arrayOfNames = array();
		preg_match_all('[<a[^>]+?class\s*?=\s*?["\']Link ListingItemTitle-module__link["\'][^>]+?href=["\'](.+?)["\'][^>]*?>(.+?)<div.+?</a>]', $__pageCode, $arrayOfNames);
		$numberOfCars = count($arrayOfNames[0]);

		//Создаём и заполняем массив с информацией о годе выпуска найденных автомобилей
		$arrayOfYearsOfRelease = array();
		preg_match_all('[<div[^>]+?class\s*?=\s*?["\']ListingItem-module__year["\']>(.+?)</div>]', $__pageCode, $arrayOfYearsOfRelease);

		//Создаём и заполняем массив с информацией о пробеге найденных автомобилей
		$mileageArray = array();
		preg_match_all('[<div[^>]+?class\s*?=\s*?["\']ListingItem-module__kmAge["\']>(.+?)</div>]', $__pageCode, $mileageArray);

		//Создаём и заполняем массив с информацией о ценах найденных автомобилей
		$arrayOfPrices = array();
		preg_match_all('[<div[^>]+?class\s*?=\s*?["\']ListingItem-module__columnCellPrice["\']>(.+?)</div>]', $__pageCode, $arrayOfPrices);

		$link = mysqli_connect("localhost", "root", '', "autoru");
		print mysqli_connect_error();
		print '<br>'.mysqli_get_host_info($link);

		//Создаём и заполняем массив со всей информацией о найденных автомобилях
		$resultingArray = array();
		for ($carIndex = 0; $carIndex < 35; $carIndex++) {
			$resultingArray[$carIndex]                   = array();
			$resultingArray[$carIndex]['Марка и модель'] = $arrayOfNames[0][$carIndex];
			$first = $resultingArray[$carIndex]['Марка и модель'];
			$resultingArray[$carIndex]['Год выпуска']    = isset($arrayOfYearsOfRelease[1][$carIndex]) ? $arrayOfYearsOfRelease[1][$carIndex] : 'Год выпуска не указан';
			$second = $resultingArray[$carIndex]['Год выпуска'];
			$resultingArray[$carIndex]['Пробег']         = isset($mileageArray[1][$carIndex]) ? $mileageArray[1][$carIndex] : 'Пробег не указан';
			$third = $resultingArray[$carIndex]['Пробег'];
			$price                                       = array();
			preg_match_all('[<div[^>]+?class\s*?=\s*?["\']ListingItemPrice-module__content["\']>(.+)]',$arrayOfPrices[1][$carIndex], $price);
			$resultingArray[$carIndex]['Цена'] = (implode($price[1])) ? implode($price[1]) : 'Цена не указана';
			$fourth = $resultingArray[$carIndex]['Цена'];
			$query = "INSERT INTO autoru (BrandAndModel, YearOfIssue, Mileage, Price) VALUE"
			. "('$first', '$second', '$third', '$fourth')";
			mysqli_query($link, $query);
		}
		$query = "SELECT * FROM autoru";
		$result = mysqli_query($link, $query);
		while ($row = mysqli_fetch_row($result))
		{
			print "<br>Марка и модель: $row[0]. Год выпуска: $row[1]. Пробег: $row[2]. Цена: $row[3].";
		}
		print '<br>'.mysqli_close($link);
		return $resultingArray;
	}
	
	
	function Parsing(){
			//Рисуем форму ввода искомых марки и модели автомобиля
			$form = '<div align="center">
						<form method="post">
							<h1>Задайте критерии поиска автомобиля</h1>
							<h4>Введите марку автомобиля: <input type="text" name="brand" required><br><br>
							Введите модель автомобиля: <input type="text" name="model" required></h4>
							<input type="submit" value="ОК">
							<input type="reset" value="Очистить">
						</form>
					</div>';
			print $form;

			//Считываем из формы марку и модель автомобиля
			$brand = strtolower(trim(filter_input(INPUT_POST, 'brand')));
			$model = strtolower(trim(filter_input(INPUT_POST, 'model')));
			if ($brand && $model) {
				$pathToAllCarsPage = 'https://auto.ru/moskva/cars/'.$brand.'/'.$model.'/all/';
				$pathToTheCarPage  = $pathToAllCarsPage.'?sort=fresh_relevance_1-desc&output_type=list&page=1';
				$pageCode          = file_get_contents($pathToTheCarPage);

				//Если марка и модель автомобиля были введены правильно, то пока есть страницы, парсим их
				if ($pageCode) {
					$page = 1;
					$temp = $this->ParsingAutoRu($pageCode);
					while (!empty($temp[0]['Марка и модель']) == '') {
						echo "<pre>";
						echo $page.' страница';
						echo "<br>";
						print_r($temp);
						echo "</pre>";
						$page++;
						$pathToTheCarPage = $pathToAllCarsPage.'?sort=fresh_relevance_1-desc&output_type=list&page='.$page;
						$pageCode         = file_get_contents($pathToTheCarPage);
						$temp = $this->ParsingAutoRu($pageCode);
					}
				}
				else
					echo 'Введите марку и модель автомобиля.';
			}
			
	}
	
}
