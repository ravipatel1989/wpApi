module.exports = {
    apps: [{
        name: 'brent-microservice',
        script: 'microservice/index.js',
        env: {
            PHP_PORT: 5000,
            WEBSOCKET_PORT: 3001,
        },
        env_production: {
            NODE_ENV: "production",
            API_URL: 'https://dev-brent.4you2test.com/wp-json',
        },
        env_development: {
            NODE_ENV: "development",
            API_URL: 'http://brent.test/wp-json',
            watch: true
        }
    }]
};
