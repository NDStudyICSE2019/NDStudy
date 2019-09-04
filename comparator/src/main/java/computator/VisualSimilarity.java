package computator;

import java.io.File;
import java.io.IOException;
import java.math.BigDecimal;
import java.nio.file.Files;
import java.util.LinkedHashMap;
import java.util.LinkedList;
import java.util.List;
import java.util.Map;

import org.opencv.imgcodecs.Imgcodecs;

import utils.ProgressBar;
import comparators.VisualAbstractComparator;
import config.Settings;
import utils.CsvUtils;
import utils.Jaccard;
import utils.Utils;

public class VisualSimilarity {

	private String name;
	private List<File> screenshots;
	private double[][] distanceMatrix;
	private VisualAbstractComparator comparator;
	private File outputCsvRaw;
	private File outputCsvNormalized;

	private Map<String, LinkedHashMap<String, BigDecimal>> rawDistanceMap;
	private Map<String, LinkedHashMap<String, BigDecimal>> normalizedDistanceMap;

	int matchesDone;
	double percent;
	int totalMatches;

	private boolean normalize = false;

	public VisualSimilarity(List<File> s, VisualAbstractComparator comp) {
		this.screenshots = s;
		this.comparator = comp;
		this.outputCsvNormalized = new File(Settings.outputDirApp + Settings.app + "-" + comp.getName() + "-normalized.csv");
		this.outputCsvRaw = new File(Settings.outputDirApp + Settings.app + "-" + comp.getName() + "-raw.csv");
	}

	public void computeSimilarity() {

		/* do not compute the same matrix unnecessarily. */
		if (Files.exists(outputCsvRaw.toPath())) {
			return;
		}

		int size = screenshots.size();
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

			if (comparator.getName().equals("VISUAL-PDiff")) {
				comparator.setPage1(screenshots.get(i).toPath().toString());
			} else {
				comparator.setPage1(Imgcodecs.imread(screenshots.get(i).toPath().toString()));
			}

			for (int j = 0; j < screenshots.size(); j++) {

				/* skip unnecessary computations. */
				if (i <= j)
					continue;

				if (comparator.getName().equals("VISUAL-PDiff")) {
					comparator.setPage2(screenshots.get(j).toPath().toString());
				} else {
					comparator.setPage2(Imgcodecs.imread(screenshots.get(j).toPath().toString()));
				}

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

		System.out.println();

		/* save raw matrix. */
		//distanceMatrix = Jaccard.truncateMatrix(distanceMatrix);
		//rawDistanceMap = Utils.convertToDistanceMap(distanceMatrix, screenshots);
		

		try {
			if (!Files.exists(outputCsvRaw.toPath())) {
				Files.createFile(outputCsvRaw.toPath());
			}
			CsvUtils.writeCsv(screenshots, distanceMatrix, outputCsvRaw.toPath().toString());
		} catch (IOException e) {
			e.printStackTrace();
		}

		if (normalize) {
			/* save normalized matrix as well. */
			distanceMatrix = Jaccard.normalizeMatrix(distanceMatrix);
			normalizedDistanceMap = Utils.convertToDistanceMap(distanceMatrix, screenshots);

			try {
				if (!Files.exists(outputCsvNormalized.toPath())) {
					Files.createFile(outputCsvNormalized.toPath());
				}
				CsvUtils.writeCsv(screenshots, distanceMatrix, outputCsvNormalized.toPath().toString());
			} catch (IOException e) {
				e.printStackTrace();
			}
		}
		/* create heat map. */
		Utils.createHeatChart(comparator.getName(), distanceMatrix);

		// Utils.printMap(rawDistanceMap);
		// if (normalize) {
		// Utils.printMap(normalizedDistanceMap);

	}

	public String getName() {
		return Settings.app + "-" + this.name;
	}

	public List<File> getScreenshotList() {
		return this.screenshots;
	}

	public void setScreenshotList(List<File> screenshots) {
		this.screenshots = screenshots;
	}

	public double[][] getDistanceMatrix() {
		return this.distanceMatrix;
	}

	public void setDistanceMatrix(double[][] matrix) {
		this.distanceMatrix = matrix;
	}

	public VisualAbstractComparator getComparator() {
		return this.comparator;
	}

	public void setComparator(VisualAbstractComparator comp) {
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
