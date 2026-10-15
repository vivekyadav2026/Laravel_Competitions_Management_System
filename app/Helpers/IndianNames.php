<?php

namespace App\Helpers;

class IndianNames
{
    protected static array $firstNames = [
        'Aman', 'Rahul', 'Vikram', 'Rohit', 'Deepak', 'Sachin', 'Aditya', 'Suresh',
        'Ankit', 'Pooja', 'Priya', 'Neha', 'Sunil', 'Vikas', 'Manoj', 'Harsh',
        'Karan', 'Rohan', 'Sanjay', 'Naveen', 'Ajay', 'Ravi', 'Vijay', 'Mohit',
        'Abhishek', 'Gaurav', 'Manish', 'Kunal', 'Ashish', 'Varun', 'Tarun', 'Pawan',
        'Alok', 'Sumit', 'Amit', 'Rajesh', 'Dinesh', 'Mukesh', 'Rakesh', 'Anil',
        'Pradeep', 'Sandeep', 'Jitendra', 'Dharmendra', 'Narendra', 'Suraj', 'Pankaj',
        'Shubham', 'Mayank', 'Anurag', 'Vivek', 'Nikhil', 'Yash', 'Ritik', 'Ayush'
    ];

    protected static array $lastNames = [
        'Sharma', 'Verma', 'Singh', 'Kumar', 'Patel', 'Yadav', 'Raj', 'Gupta',
        'Mishra', 'Meena', 'Joshi', 'Choudhary', 'Mehta', 'Thakur', 'Pandey',
        'Tiwari', 'Dubey', 'Shukla', 'Chauhan', 'Rathore', 'Saini', 'Bishnoi',
        'Gurjar', 'Malik', 'Tanwar', 'Kashyap', 'Chaurasia', 'Goswami', 'Rao'
    ];

    /**
     * Get a random authentic Indian full name.
     */
    public static function getRandomName(): string
    {
        $first = self::$firstNames[array_rand(self::$firstNames)];
        $last = self::$lastNames[array_rand(self::$lastNames)];
        return "{$first} {$last}";
    }

    /**
     * Get a short display name (e.g. Rahul S. or Aman V.)
     */
    public static function getShortName(): string
    {
        $first = self::$firstNames[array_rand(self::$firstNames)];
        $last = self::$lastNames[array_rand(self::$lastNames)];
        return "{$first} " . substr($last, 0, 1) . ".";
    }

    /**
     * Get all possible name combinations.
     */
    public static function all(): array
    {
        $all = [];
        foreach (self::$firstNames as $first) {
            foreach (self::$lastNames as $last) {
                $all[] = "{$first} {$last}";
            }
        }
        return $all;
    }
}
