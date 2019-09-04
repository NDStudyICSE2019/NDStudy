package computator;

import java.io.IOException;
import java.util.LinkedList;
import java.util.Map;

import config.Settings;
import utils.JsonUtils;

public class Distance {

	private static Map<Integer, LinkedList<String>> goldStandardMap;
	private Map<Integer, LinkedList<String>> normalizedDistanceMap;

	
	public static void main(String[] args) throws IOException {
		
		// read the gold standard
//		goldStandardMap = CsvUtils.readGoldStandard(Settings.outputDirApp + "clustering-gold-standard.csv");
		
//		Utils.printHashMap(goldStandardMap);
		
//		System.out.println(goldStandardMap.get(3).size());
		
		JsonUtils.readJson(Settings.outputDirApp + "clustering-gold-standard.json");
		
		JsonUtils.readJson(Settings.outputDirApp + "petclinic-clustering-results.json");
		
		// read the petclinic clustering
		
		
		// calculate the PED for each cluster
		
		
	}
}
