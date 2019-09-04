package utils;

import java.io.BufferedWriter;
import java.io.File;
import java.io.IOException;
import java.io.Reader;
import java.nio.file.Files;
import java.nio.file.Paths;
import java.util.ArrayList;
import java.util.Arrays;
import java.util.List;
import java.util.Map;

import org.apache.commons.csv.CSVFormat;
import org.apache.commons.csv.CSVParser;
import org.apache.commons.csv.CSVPrinter;
import org.apache.commons.csv.CSVRecord;
import org.apache.commons.io.FilenameUtils;

import config.Settings;

public class CsvUtils {
	

	public static void writePedCsvFile(Map<String, Integer> pedBestMap, String name) {

		String filename = Settings.outputDirApp + name + ".csv";

		BufferedWriter writer = null;
		try {
			writer = Files.newBufferedWriter(Paths.get(filename));
		} catch (IOException e) {
			e.printStackTrace();
		}

		CSVPrinter csvPrinter;
		try {

			csvPrinter = new CSVPrinter(writer, CSVFormat.DEFAULT);

			for (String key : pedBestMap.keySet()) {
				List<String> data = new ArrayList<>();
				data.add(key);
				data.add(pedBestMap.get(key).toString());
				csvPrinter.printRecord(data);
			}

			csvPrinter.flush();
			csvPrinter.close();

		} catch (IOException e) {
			e.printStackTrace();
		}
	}

	public static void writeCsv(List<File> states, double[][] dm, String name) throws IOException{


		String[] names = new String[states.size()];

		for (int i = 0; i < states.size(); i++) {
			names[i] = states.get(i).getName();
		}

		writeCsv(names, dm, name);
	}
	
	public static String[] getStateNamesFromCSV(String csvFilePath) {
		File csvFile = new File(csvFilePath);
		try {
			Reader reader = Files.newBufferedReader(Paths.get(csvFilePath));
			CSVParser csvParser = new CSVParser(reader, CSVFormat.DEFAULT);
			int i = 0;
			CSVRecord nameRecord = csvParser.getRecords().get(0);
			String [] names = new String[nameRecord.size()];
			for (int j = 0; j < nameRecord.size(); j++) {
				// String data1 = csvRecord.get(j);
				// System.out.println(data1);
				/*
				 * if(data1.isEmpty()) { System.out.println(j); }
				 */
				names[j] = FilenameUtils.getBaseName(new String(nameRecord.get(j)));
			}
			return names;
		} catch (Exception ex) {
			ex.printStackTrace();
		}
		return null;
	}

	public static double[][] readCSV(String csvFilePath) {
		File csvFile = new File(csvFilePath);
		double[][] data = new double[1][1];
		boolean sizeFound = false;
		int size = 1;
		try {
			Reader reader = Files.newBufferedReader(Paths.get(csvFilePath));
			CSVParser csvParser = new CSVParser(reader, CSVFormat.DEFAULT);
			int i = 0;

			for (CSVRecord csvRecord : csvParser) {
				if (!sizeFound) {
					size = csvRecord.size();
					data = new double[size][size];
					sizeFound = true;
					continue;
				}
				for (int j = 0; j < csvRecord.size(); j++) {
					// String data1 = csvRecord.get(j);
					// System.out.println(data1);
					/*
					 * if(data1.isEmpty()) { System.out.println(j); }
					 */
					data[i][j] = new Double(csvRecord.get(j));
				}
				i++;
			}
		} catch (Exception ex) {
			ex.printStackTrace();
		}
		return data;
	}

	public static void writeCsv(String[] states, double[][] dm, String name)  throws IOException {
		BufferedWriter writer = Files.newBufferedWriter(Paths.get(name));
		
		CSVPrinter csvPrinter = new CSVPrinter(writer, CSVFormat.DEFAULT.withHeader(states));

		for (int i = 0; i < states.length; i++) {

			String[] values = new String[states.length];

			for (int j = 0; j < states.length; j++) {

				values[j] = "" + dm[i][j];

			}

			List<String> data = Arrays.asList(values);

			csvPrinter.printRecord(data);

		}

		csvPrinter.flush();
		csvPrinter.close();
	}

}
