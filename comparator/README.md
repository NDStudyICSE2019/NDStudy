1. Java Project

        build : 
              mvn package -DskipTests
        
        To create gold standards : runner.Main 
          Input :  
                 Crawl to be analyzed. 
           Output : 
                 the distance between each pair of states as computed by 10 near-duplicate techniques. 
                 Output will be created in the folder <crawl>/output

        To analyze a crawl using existing gold standard :  runner.RQ2Main
          Input: 
                Crawl to Be analyzed, Corresponding Gold Standard
          Output:
                Html Classification to be verified manually 
          Usage:
                pythonCode/analyzeCrawl.py can get statistics for the crawl after analysis
          
         
2. PythonCode
        
        1. Uses Crawljax for running crawls
        2. Uses comparator java project for analysis
        
