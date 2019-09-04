package utils;

import java.io.BufferedReader;
import java.io.FileNotFoundException;
import java.io.FileOutputStream;
import java.io.FileReader;
import java.lang.reflect.Type;
import java.util.Arrays;
import java.util.HashSet;
import java.util.Set;

import org.apache.commons.io.FilenameUtils;

import com.google.common.collect.Sets;
import com.google.common.reflect.TypeToken;
import com.google.gson.Gson;
import com.google.gson.stream.JsonReader;

public class ClusteringUtils {

	public static void normalizeClusterFile(String clusterFile) {
		try {
			// input the file content to the StringBuffer "input"
			BufferedReader file = new BufferedReader(new FileReader(clusterFile));
			String line;
			StringBuffer inputBuffer = new StringBuffer();

			while ((line = file.readLine()) != null) {
				line = line.trim();

				if (!line.startsWith("[") && !line.startsWith("]")) {
					line = "[" + line;
					if (line.endsWith(",")) {
						line = line.substring(0, line.length() - 1);
						line = line + "],";
					} else
						line = line + "]";
				}

				inputBuffer.append(line);
				inputBuffer.append('\n');
			}
			String inputStr = inputBuffer.toString();

			file.close();

			// write the new String with the replaced line OVER the same file
			FileOutputStream fileOut = new FileOutputStream(clusterFile);
			fileOut.write(inputStr.getBytes());
			fileOut.close();

		} catch (Exception e) {
			System.out.println("Problem reading file.");
		}
	}

	public static String[][] readClusterFile(String clusterFile) {
		try {
			normalizeClusterFile(clusterFile);
			Gson gson = new Gson();
			FileReader clusterFileReader = new FileReader(clusterFile);
			BufferedReader bufReader = new BufferedReader(clusterFileReader);
			JsonReader reader = new JsonReader(clusterFileReader);
			Type type = new TypeToken<String[][]>() {
			}.getType();
			Object obj = gson.fromJson(reader, type);
			String[][] clusters = (String[][]) obj;
			for (int i = 0; i < clusters.length; i++) {
				for (int j = 0; j < clusters[i].length; j++) {
					clusters[i][j] = FilenameUtils.removeExtension(clusters[i][j].trim());
				}
			}
			return clusters;
		} catch (FileNotFoundException e) {
			System.err.println("Clustering Gold Standard file not found. Exiting...");
			System.exit(1);
		} catch (Exception ex) {
			ex.printStackTrace();
			System.exit(1);
		}
		return null;
	}

	public static int getPartitionEditDistance(String[][] clusteringGS, String[][] clustering, int[] bestAssignment,
			boolean best) {

		int pedTot = 0;
		String pedConcat = "";

		if (best) {
			for (int i = 0; i < bestAssignment.length; i++) {
				Set<String> clusterGSSet = new HashSet<String>(Arrays.asList(clusteringGS[i]));
				Set<String> clusterSet = new HashSet<String>(Arrays.asList(clustering[bestAssignment[i]]));

				int ped = Sets.difference(clusterGSSet, clusterSet).size(); // + Sets.difference(clusterSet,
																			// clusterGSSet).size();

				System.out.println(Arrays.asList(clusteringGS[i]) + " <-> "
						+ Arrays.asList(clustering[bestAssignment[i]]) + " : " + ped);

				pedConcat = pedConcat.concat(ped + " + ");

				pedTot += ped; // Sets.difference(clusterGSSet, clusterSet).size();
			}
		} else {
			for (int i = 0; i < clusteringGS.length; i++) {
				Set<String> clusterGSSet = new HashSet<String>(Arrays.asList(clusteringGS[i]));
				Set<String> clusterSet = new HashSet<String>(Arrays.asList(clustering[i]));
				pedTot += Sets.difference(clusterGSSet, clusterSet).size();
			}
		}

		System.out.println(pedConcat);
		return pedTot;

	}

}
