package utils;

import java.io.File;
import java.io.IOException;

import org.apache.commons.io.FileUtils;

import com.google.gson.Gson;
import com.google.gson.GsonBuilder;
import com.google.gson.JsonSyntaxException;

import utils.GSJson.ClassificationJson;

public class JsonUtils {

	static Gson gson = new GsonBuilder().setPrettyPrinting().create();
	
	public static void readJson(String jsonFilePath) throws IOException {
		
		ClusterOutput[] clusters = gson.fromJson(FileUtils.readFileToString(new File(jsonFilePath)), ClusterOutput[].class);
		
		for (ClusterOutput clusterOutput : clusters) {
			System.out.println(clusterOutput);
		}
		
	}
	
	
	public static ClassificationJson readGsJson(String jsonPath) {
		try {
			ClassificationJson gsJson = gson.fromJson(FileUtils.readFileToString(new File(jsonPath)), ClassificationJson.class);
			System.out.println(gsJson.getStates().size());
			System.out.println(gsJson.getPairs().size());
			return gsJson;
		} catch (JsonSyntaxException | IOException e) {
			e.printStackTrace();
		}
		
		return null;
	}

}
