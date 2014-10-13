package com.asla.Geolocation;

import android.app.Activity;
import android.content.Context;
import android.location.Location;
import android.location.LocationListener;
import android.location.LocationManager;
import android.os.Bundle;
import android.widget.TextView;

import static android.content.Context.*;
import static android.location.LocationManager.*;

// reference https://www.youtube.com/watch?v=7-n6p6RxSS8
public class Geolocation extends Activity {
    private TextView textLat;
    private TextView textLong;

    /**
     * Called when the activity is first created.
     */
    @Override
    public void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.main);

        textLat = (TextView)findViewById(R.id.textLat);
        textLong = (TextView)findViewById(R.id.textLong);

        final LocationManager myManager = (LocationManager)getSystemService(LOCATION_SERVICE);
        final LocationListener myListener = new LocationListener() {
            @Override
            public void onLocationChanged(Location location) {
                if (location != null) {
                    final double pLong = location.getLongitude();
                    final double pLat = location.getLatitude();

                    textLat.setText(Double.toString(pLat));
                    textLong.setText(Double.toString(pLong));
                }
            }

            @Override
            public void onStatusChanged(String s, int i, Bundle bundle) {

            }

            @Override
            public void onProviderEnabled(String s) {

            }

            @Override
            public void onProviderDisabled(String s) {

            }
        };

        myManager.requestLocationUpdates(GPS_PROVIDER, 0, 0, myListener);
    }
}
