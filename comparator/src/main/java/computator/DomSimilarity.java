package computator;

import java.io.File;
import java.io.IOException;
import java.math.BigDecimal;
import java.nio.file.Files;
import java.util.LinkedHashMap;
import java.util.LinkedList;
import java.util.List;
import java.util.Map;

import comparators.DomAbstractComparator;
import config.Settings;
import utils.CsvUtils;
import utils.Jaccard;
import utils.ProgressBar;
import utils.Utils;

public class DomSimilarity {

	private String name;
	private List<File> doms;

	private double[][] distanceMatrix;

	private Map<String, LinkedHashMap<String, BigDecimal>> rawDistanceMap;
	private Map<String, LinkedHashMap<String, BigDecimal>> normalizedDistanceMap;

	private DomAbstractComparator comparator;
	private File outputCsvRaw;
	private File outputCsvNormalized;
	
	int matchesDone;
	double percent;
	int totalMatches;

	private boolean normalize = false;

	public DomSimilarity(List<File> domList, DomAbstractComparator comp) {
		this.doms = domList;
		this.comparator = comp;
		this.outputCsvNormalized = new File(Settings.outputDirApp + Settings.app + "-" + comp.getName() + "-normalized.csv");
		this.outputCsvRaw = new File(Settings.outputDirApp + Settings.app + "-" + comp.getName() + "-raw.csv");
	}

	public void computeSimilarity() {

		/* do not compute the same matrix unnecessarily. */
		if (Files.exists(outputCsvRaw.toPath())) {
			return;
		}

		int size = doms.size();
		distanceMatrix = new double[size][size];
		
		List<Integer> v = new LinkedList<Integer>();
		
		if (Settings.PROGRESS) {
			totalMatches = size * size;
			for (int count = 1; count < 101; count++) {
				v.add(totalMatches * count / 100);
			}

			matchesDone = 0;
			percent = 0;
			ProgressBar.printProgBar((int) percent);
		}

		/* build the similarity matrix for all screenshots. */
		for (int i = 0; i < size; i++) {

			comparator.setPage1(doms.get(i).toPath().toString());

			for (int j = 0; j < doms.size(); j++) {

				/* skip unnecessary computations. */
				if (i <= j)
					continue;

				comparator.setPage2(doms.get(j).toPath().toString());

				/* get the similarity measure. */
				distanceMatrix[i][j] = distanceMatrix[j][i] = comparator.computeDistance();

				if (Settings.PROGRESS) {
					matchesDone = matchesDone + 2;

					if (v.contains(matchesDone)) {
						percent++;
						ProgressBar.printProgBar((int) percent * 2);
						System.out.print("\t" + matchesDone + "/" + totalMatches);
					}
				}
				
				/* free the resources. */
				System.gc();
			}

		}

		/* save raw matrix (and map). */
		//distanceMatrix = Jaccard.truncateMatrix(distanceMatrix);
		//rawDistanceMap = Utils.convertToDistanceMap(distanceMatrix, doms);

		try {
			if (!Files.exists(outputCsvRaw.toPath())) {
				Files.createFile(outputCsvRaw.toPath());
			}
			CsvUtils.writeCsv(doms, distanceMatrix, outputCsvRaw.toPath().toString());
		} catch (IOException e) {
			e.printStackTrace();
		}

		if (normalize) {
			/* save normalized matrix (and map) as well. */
			distanceMatrix = Jaccard.normalizeMatrix(distanceMatrix);
			normalizedDistanceMap = Utils.convertToDistanceMap(distanceMatrix, doms);

			try {
				if (!Files.exists(outputCsvNormalized.toPath())) {
					Files.createFile(outputCsvNormalized.toPath());
				}
				CsvUtils.writeCsv(doms, distanceMatrix, outputCsvNormalized.toPath().toString());
			} catch (IOException e) {
				e.printStackTrace();
			}
		}

		/* create heat map. */
		Utils.createHeatChart(comparator.getName(), distanceMatrix);

		// Utils.printMap(rawDistanceMap);
		// if (normalize)
		// Utils.printMap(normalizedDistanceMap);

	}

	public String getName() {
		return Settings.app + "-" + this.name;
	}

	public List<File> getScreenshotList() {
		return this.doms;
	}

	public void setScreenshotList(List<File> screenshots) {
		this.doms = screenshots;
	}

	public double[][] getDistanceMatrix() {
		return this.distanceMatrix;
	}

	public void setDistanceMatrix(double[][] matrix) {
		this.distanceMatrix = matrix;
	}

	public DomAbstractComparator getComparator() {
		return this.comparator;
	}

	public void setComparator(DomAbstractComparator comp) {
		this.comparator = comp;
	}

	public File getNormalizedOutputFile() {
		return this.outputCsvNormalized;
	}

	public void setNormalizedOutputFile(File outputFile) {
		this.outputCsvNormalized = outputFile;
	}

	public File getRawOutputFile() {
		return this.outputCsvRaw;
	}

	public void setRawOutputFile(File outputFile) {
		this.outputCsvRaw = outputFile;
	}

}
