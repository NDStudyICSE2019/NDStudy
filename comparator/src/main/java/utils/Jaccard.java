package utils;

import java.io.File;
import java.util.Arrays;
import java.util.HashSet;
import java.util.List;
import java.util.Set;

import com.google.common.collect.Sets;

public class Jaccard {

	/**
	 * Calculate the Jaccard similarity between two partitions
	 * 
	 * @param m1
	 * @param m2
	 * @param print
	 * @return
	 * @throws Exception
	 */
	public static double[][] calculateJaccard(String[][] m1, String[][] m2, boolean print) throws Exception {

		double[][] distanceMatrix;

		int rows = m1.length;
		int columns = m2.length;

		distanceMatrix = new double[rows][columns];
		double[] maxRows = new double[rows];
		double[] maxColumns = new double[columns];

		for (int i = 0; i < m1.length; i++) {

			for (int j = 0; j < m2.length; j++) {

				String[] c1 = m1[i];
				String[] c2 = m2[j];

				distanceMatrix[i][j] = jaccard(c1, c2);

				if (distanceMatrix[i][j] > maxRows[i]) {
					maxRows[i] = distanceMatrix[i][j];
				}

				if (distanceMatrix[i][j] > maxColumns[j]) {
					maxColumns[j] = distanceMatrix[i][j];
				}
			}

		}

		if (print) {
			printDistanceMatrix(null, distanceMatrix, rows, columns, maxRows, maxColumns);
		}

		double total = 0.0;

		for (double i : maxRows)
			total += i;

		double[] avg = new double[3];

		avg[0] = total / rows;

		total = 0.0;

		for (double i : maxColumns)
			total += i;

		avg[1] = total / columns;

		avg[2] = (avg[0] + avg[1]) / 2;

		return distanceMatrix;

	}

	/**
	 * Print the distance matrix
	 * 
	 * @param screenshots
	 * 
	 * @param dm
	 * @param columns
	 * @param rows
	 * @param maxRows
	 * @param maxColumns
	 */
	public static void printDistanceMatrix(List<File> screenshots, double[][] dm, int rows, int columns, double[] maxRows, double[] maxColumns) {

		System.out.println("SIMILARITY MATRIX");

		for (int c = 0; c < columns; c++) {
			System.out.print("|" + c + "|\t");
		}

		System.out.print("\n");

		for (int c = 0; c < columns; c++) {
			System.out.print("-----------");
		}

		System.out.print("\n");

		for (int i = 0; i < rows; i++) {

			System.out.print("|" + i + "|\t");

			for (int j = 0; j < columns; j++) {
				System.out.print(String.format("%03.2f\t", dm[i][j]));
			}
			System.out.print(String.format("MAX: %03.2f", maxRows[i]));
			// System.out.println("MAX: " + Arrays.toString(sumVector));
			System.out.print("\n");
		}

		for (int c = 0; c < columns; c++) {
			System.out.print("-----------");
		}

		System.out.print("\n");

		System.out.print("MAX:\t");

		for (int i = 0; i < columns; i++) {
			System.out.print(String.format("%03.2f\t", maxColumns[i]));
		}

		System.out.print("\n");

		for (int c = 0; c < columns; c++) {
			System.out.print("-----------");
		}

		System.out.print("\n");
	}

	/**
	 * Calculate Jaccard similarity between two sets
	 * 
	 * @param l1
	 * @param l2
	 * @return
	 */
	private static double jaccard(String[] l1, String[] l2) {

		if (l1 == null || l2 == null || l1.length == 0 || l2.length == 0) {
			try {
				throw new Exception("Clusters cannot be empty");
			} catch (Exception e) {
				e.printStackTrace();
			}
		}

		Set<String> set1 = new HashSet<String>(Arrays.asList(l1));
		Set<String> set2 = new HashSet<String>(Arrays.asList(l2));

		double intersection = Sets.intersection(set1, set2).size();
		double union = Sets.union(set1, set2).size();

		double result = intersection / union;

		return 1.0 - result;
	}

	private static double getMatrixMax(double[][] dm) {
		double max = 0.0;
		for (int i = 0; i < dm.length; i++) {
			for (int j = 0; j < dm.length; j++) {
				max = ((dm[i][j] > max) ? dm[i][j] : max);
			}
		}
		return max;
	}

	public static double[][] normalizeMatrix(double[][] dm) {

		double max = getMatrixMax(dm);
		int size = dm.length;
		double[][] normalized = new double[size][size];

		/*
		 * for each entry x, computes the min-max normalization score, which is given by
		 * (x - min(dm)) / (max(dm) - min(dm)). Requires two aux functions that get the
		 * min and the max values of the matrix respectively
		 */
		if (max == 0.0) {
			return dm;
		}
		
		for (int i = 0; i < dm.length; i++) {
			for (int j = 0; j < dm.length; j++) {
				normalized[i][j] = (dm[i][j] / max) * 10;
				normalized[i][j] = Math.floor(normalized[i][j] * 100) / 100;
			}
		}

		return normalized;
	}

	public static double[][] truncateMatrix(double[][] dm) {

		for (int i = 0; i < dm.length; i++) {
			for (int j = 0; j < dm.length; j++) {
				dm[i][j] = Math.floor(dm[i][j] * 1000) / 1000;
			}
		}

		return dm;
	}

}
