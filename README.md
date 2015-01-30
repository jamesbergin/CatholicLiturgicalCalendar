# CatholicLiturgicalCalendar
Code for automatically figuring out the Liturgical Season and changing the colour on a website

Back in 2006, I started to work on my first Catholic blog. As I started to explore Wordpress as a CMS and how the themes work within that platform, I wanted to get the colours of the site to change with the liturgical seasons of the Church - i.e. violet for Lent and Advent, gold for Easter etc.

So, I searched the web trying to find the algorithm that is used to calculate when Easter is, as all the other dates in the calendar basically stem from that. It turns out that there are multiple versions of this algorithm that have been developed and published over the centuries; Wikipedia has more information (http://en.wikipedia.org/wiki/Computus#Algorithms) if you're interested.

At any rate, the algorithm I ended up using is known as the Meeus/Jones/Butcher algorithm and was originally sourced from Marcos J. Montes (http://www.smart.net/~mmontes/nature1876.html). According to Marcos and Wikipedia, "the actual origin of this algorithm appears to be by an anonymous correspondent from New York to Nature in 1876."

I updated the code in 2013, and thought that I would make it open source in case it is of use to other Catholic techies out there for use on their projects. It doesn't require any updating of tables as it calculates everything from the server date. 

Hope it's of use to someone! :)

@jamesbergin
January 2015
