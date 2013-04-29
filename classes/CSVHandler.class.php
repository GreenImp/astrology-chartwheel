<?php
/**
 * Author: leelangley
 * Date Created: 29/04/2013 10:35
 */
 
class CSVHandler{
	/**
	 * Takes a file name and parses the contents, as a CSV.
	 * Returns an array of the CSV data, on success or an
	 * empty array on failure.
	 *
	 * @param $file
	 * @return array
	 */
	public static function parseFile($file){
		$rows = array();

		// open the file
		if(false !== ($handle = fopen($file, 'r'))){
		    while(false !== ($row = fgetcsv($handle, 1000, ","))){
				// only add the row if it's not empty
		        if(count($row) > 0){
					$rows[] = $row;
				}
		    }

			// close the file
		    fclose($handle);
		}

		return $rows;
	}

	/**
	 * Takes a string and returns a an array,
	 * from CSV data
	 *
	 * @param $str
	 * @return array
	 */
	public static function parseString($str){
		return str_getcsv($str);
	}

	/**
	 * Takes CSV data (either as a pre-formatted CSV
	 * string or and array) and stored it in a file
	 * at the given $filename
	 *
	 * @param $data
	 * @param $filename
	 * @return bool
	 */
	public static function buildFile($data, $filename){
		if(!is_array($data)){
			// data isn't an array - convert it into one
			$data = self::parseString($data);
		}

		if(false !== ($fp = fopen($filename, 'w'))){
			foreach($data as $row){
				fputcsv($fp, $row);
			}

			fclose($fp);

			return true;
		}

		return false;
	}
}