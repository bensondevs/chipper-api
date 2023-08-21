# Senior Fullstack Developer Test

Welcome, developer!

This is the repository of the REST API of _Chipper_, a fictional microblogging application. It has the following features:

### Registration
- A guest can register

### Authentication
- A user can login with email and password
- A users can not authenticate with an invalid password
- A user can logout

### Post
- A guest can not create a post
- A user can create a post
- A user can update a post
- A user can update a post by other user
- A user can destroy one of his posts

### Favorite
- A guest can not favorite a post
- A user can favorite a post
- A user can remove a post from his favorites
- A user can not remove a non favorited item

As part of our interview process, we'd like to invite you to tackle a technical challenge and share your solution with us. Are you ready? Let's get started!

# Tasks

Please invest some time in acquainting yourself with the codebase, including Models, Controllers, and the database structure. Then, proceed to address the following tasks one by one.

## 1. Add the ability for users to favorite users

At present, users have the option to mark posts as favorites. 

The goal of this task is to expand this functionality to allow users to also designate their favorite _users_. Imagine this as a "follow" feature.

We'd like to use the following routes:

`POST users/{user}/favorite` to add a user to my list of favorites.
`DELETE users/{user}/favorite` to remove a favorited user from my list of favorites.

To achieve this, we need to deprecate the `post_id` column in the `favorites` table. Instead, we'll introduce new columns that support a polymorphic relationship. This relationship will allow the creation of favorites for various models. In this case, `User`.

It's important to note that the database is already in production. Therefore, you should create new migrations for this task rather than modifying the existing ones.

Please make sure to add testing to the feature, to make sure the user can favorite authors and still can favorite posts. Include all the relevant tests in the `tests/Feature/FavoriteTest.php`.

Make sure a user can't favorite himself.

✋ **BEFORE YOU BEGIN**

Please update the following line in this `README.md` file to include your estimate of the time required for completion.

Estimated Time Required: [Your Estimate Here]

After updating the estimate, commit your changes using the following command:
`git add README.md && git commit -m "Task 1 estimated" && git push`

Please make sure to commit this right before you start working on the task.

🏁 **ONCE YOU HAVE COMPLETED THE TASK**

After implementing the changes, commit your work using the following commands:
`git add -A && git commit -m "Added the ability for users to favorite authors" && git push`

## 2. Update the payload of `GET /favorites` to list posts and users

The objective of this task is to modify the endpoint responsible for providing a user's list of favorites. Currently, the endpoint simply offers a list of all favorites, but our requirement is for it to return a JSON structure like this:

```
{
  "data": {
    "posts": [
      {
        "id": 1,
        "title": "All about cats",
        "body": "Lorem Ipsum...",
        "user": { "id": 5, "name": "Jack" }
      },
      {
        "id": 1,
        "title": "All about dogs",
        "body": "Lorem Ipsum...",
        "user": { "id": 1, "name": "Zara" }
      }
    ],
    "users": [
      { "id": 5, "name": "Jack" },
      { "id": 8, "name": "Jane" },
      { "id": 9, "name": "Luke" }
    ]
  }
}
```

As in the previous task, please make sure to add the relevant tests in `tests/Feature/FavoriteTest.php`.

✋ **BEFORE YOU BEGIN**

Please update the following line in this `README.md` file to include your estimate of the time required for completion.

Estimated Time Required: [Your Estimate Here]

After updating the estimate, commit your changes using the following command:
`git add README.md && git commit -m "Task 2 estimated" && git push`

Please make sure to commit this right before you start working on the task.

🏁 **ONCE YOU HAVE COMPLETED THE TASK**

After implementing the changes, commit your work using the following commands:
`git add -A && git commit -m "Updated structure of favorites index payload" && git push`

## 3. Notify a user whenever one of their favored users creates a new post.

The users of Chipper want to stay up to date with the people they are following.

Every time a user creates a new post, the system should notify via email to all the users who have them in their list of favorites. The goal of this task is to code that feature.

Please use [Laravel notifications](https://laravel.com/docs/10.x/notifications). The users should be notified by email only.

For development, feel free to use a service like Mailtrap and the `smtp` driver to send your email messages to a "dummy" mailbox. For testing, the `array` driver is fine.

It is of utmost importance that the process of delivering these emails does not lead to delays in the API response for the `POST /posts` endpoint.

In addition to the implementation, please ensure comprehensive test coverage for this new feature.

✋ **BEFORE YOU BEGIN**

Please update the following line in this `README.md` file to include your estimate of the time required for completion.

Estimated Time Required: [Your Estimate Here]

After updating the estimate, commit your changes using the following command:
`git add README.md && git commit -m "Task 3 estimated" && git push`

Please make sure to commit this right before you start working on the task.

🏁 **ONCE YOU HAVE COMPLETED THE TASK**

After implementing the feature, commit your work using the following commands:
`git add -A && git commit -m "Added notification for users when new post of favorite user is created" && git push`

# Ready for the next challenge?

Congratulations! You completed the first part of this interview coding challenge. We'll now leave the API behind and enter the front-end realm.

Please go to this repository and follow the instructions. Thank you and good luck!


