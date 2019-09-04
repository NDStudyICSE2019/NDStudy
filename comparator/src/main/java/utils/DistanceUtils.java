package utils;

import java.io.File;
import java.util.ArrayList;
import java.util.List;

import org.opencv.core.Mat;

import config.Settings;

public class DistanceUtils {
	
	public enum distMetrics{
		EUCLIDEAN;
	}
	
	public static double getEuclideanDistance(double val1, double val2) {
		return Math.abs(val1-val2);
	}

	public static double[][] getMatrixDistance(double[][] dm1, double[][]dm2, distMetrics metric) {
		int size = dm1.length;
		double[][] distMatrix = new double[size][size];
		for( int i=0; i<size; i++) {
			for(int j=0; j<size; j++) {
				
				// Skipping calculation for one half of the matrix
				if(i<=j)
					continue;
				
				if(metric == distMetrics.EUCLIDEAN) {
					double dist = getEuclideanDistance(dm1[i][j], dm2[i][j]);
					distMatrix[i][j] = dist;
					distMatrix[j][i] = dist;
				}
			}
		}
		
		return distMatrix;
	}
	
	public static double getSumOfMatrixElements(double [][] dm1) {
		int size = dm1.length;
		
		double ret = 0.0;
		
		for( int i=0; i<size; i++) {
			for(int j=0; j<size; j++) {
				
				// Skipping calculation for one half of the matrix
				if(i<=j)
					continue;
				
				ret += dm1[i][j];
			}
		}
		return ret;
	}
	
	public static void main(String args[]) {
		
		List<File> csvFiles = UtilsFileGetters.getCSVs(Settings.outputDirApp);
		
		File gs_file = null;
		ArrayList<File> normalizedFiles= new ArrayList<File>();
		
		for(File file: csvFiles) {
			if(file.getName().endsWith("-gs.csv"))
				gs_file = file;
			if(file.getName().endsWith("-normalized.csv"))
				normalizedFiles.add(file);
		}
		
		double[][] gs_m = CsvUtils.readCSV(gs_file.getAbsolutePath());
		int size = gs_m.length;
		
		double[] maxRows = new double[size];
		double[] maxColumns = new double[size];
		
		for(File file: normalizedFiles) {
			double [][] data = CsvUtils.readCSV(file.getAbsolutePath());
			double[][] distm = getMatrixDistance(gs_m, data, distMetrics.EUCLIDEAN);
			double dist_raw = getSumOfMatrixElements(distm);
			System.out.println( file.getName() + " : " + dist_raw);
		}
		
	
	}
}
