const { pricesSum } = require('../services/sort');
const { Client } = require("@googlemaps/google-maps-services-js");

const client = new Client({});

const distanceMatrixMap = (user, userAddress, stores, locations, res) => {
	client.distancematrix({
		params: {
			origins: [ locations ],
			destinations: [ userAddress ],
			key: process.env.GMAPS_API_KEY,
		}
	})
	.then(async (resApi) => {
		if (resApi.status === 200) {
			if (resApi.data.status === 'OK') {

				let storesDistanceMatrix = [];

				resApi.data.rows.forEach((row, index) => {
					if (row.elements[0].distance.value < 5000) {
						storesDistanceMatrix.push({
							_id: stores[index]._id,
							location: stores[index].location,
							name: stores[index].name,
							logo: stores[index].logo,
							distance: row.elements[0].distance,
							duration: row.elements[0].duration,
							menu: stores[index].menu
						});
					}
				});

				try {
					let storesAfterCartCheck = await pricesSum(user, storesDistanceMatrix);

					storesAfterCartCheck.sort((a, b) => a.duration.value - b.duration.value);
					// TODO core sort algorithm 

					res.send({
						'stores': storesAfterCartCheck 
					});
				} 
				catch (error) {
					console.log(error);
					res.send({ 
						'error': 'An unexpected error occured'
					});
				}
			}
		}
	})
	.catch((error) => {
		console.log(error);
		res.send({
			'error': error
		});
	});
};

module.exports = {
	distanceMatrixMap
};