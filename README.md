ezredis
=======
Abstraction Layer for Redis Database

changelog 0.2alpha :
- add submodule for others php libraries predis & redisent


TODO :
======
- add a library redis if they can not compile phpredis
- 


Benchmark :
php extension/ezredis/bin/php/benchmark.php ezredis 5000 500 3 verbose

fast iteration : 5000
slow iteration : 500
repeat : 3

================================= ezpublish =======================================
-- Bottom Line for redisent: Tests completed a in 6.243697 seconds in average, with 2.62 mb memory usage
-- Bottom Line for predis: Tests completed a in 6.569632 seconds in average, with 2.62 mb memory usage
-- Bottom Line for phpredis: Tests completed a in 3.464517 seconds in average, with 0.79 mb memory usage
-- Bottom Line for ezredis: Tests completed a in 4.294684 seconds in average, with 5.51 mb memory usage

================================= native =======================================
-- Bottom Line for predis: Tests completed in 6.835461 seconds in average, with 1.05 mb memory usage
-- Bottom Line for redisent: Tests completed in 6.266989 seconds in average, with 0.79 mb memory usage
-- Bottom Line for phpredis: Tests completed in 3.720065 seconds in average, with 0.52 mb memory usage
