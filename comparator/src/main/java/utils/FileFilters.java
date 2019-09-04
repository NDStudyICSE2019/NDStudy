package utils;

import java.io.File;
import java.io.FileFilter;
import java.io.FilenameFilter;

import config.Settings;

public class FileFilters {

	public static FileFilter directoryFilter = new FileFilter() {
		public boolean accept(File file) {
			return file.isDirectory();
		}
	};

	public static FilenameFilter htmlFilesFilter = new FilenameFilter() {
		public boolean accept(File dir, String name) {
			return name.toLowerCase().endsWith(Settings.HTML_EXT);
		}
	};

	public static FilenameFilter screenshotsFilter = new FilenameFilter() {
		public boolean accept(File dir, String name) {
			return (name.endsWith(Settings.PNG_EXT));
		}
	};

	public static FilenameFilter csvFilter = new FilenameFilter() {
		public boolean accept(File dir, String name) {
			return (name.endsWith(Settings.CSV_EXT));
		}
	};

	public static FilenameFilter clusterFileFilter = new FilenameFilter() {
		public boolean accept(File dir, String name) {
			if (name.endsWith("clustering-results.json"))
				return true;
			return false;
		}
	};

}
